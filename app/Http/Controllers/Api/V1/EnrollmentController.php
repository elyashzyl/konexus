<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ActionReasonRequest;
use App\Http\Requests\Api\EnrollmentRequest;
use App\Http\Requests\Api\EnrollmentRequirementItemRequest;
use App\Http\Requests\Api\EnrollmentTransferRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\EnrollmentRequirementItemResource;
use App\Http\Resources\EnrollmentTransferResource;
use App\Http\Resources\StudentSearchResource;
use App\Models\Enrollment;
use App\Services\EnrollmentRequirementService;
use App\Services\EnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends CrudController
{
    public function __construct(EnrollmentService $service)
    {
        $this->modelClass = Enrollment::class;
        $this->resourceClass = EnrollmentResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return EnrollmentRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Enrollment';
    }

    /**
     * Search students for the New Enrollment intake step.
     */
    public function searchStudent(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Enrollment::class);

        $term = Str::of($request->string('q', ''))->trim()->toString();

        return $this->success([
            'items' => StudentSearchResource::collection($this->service->searchStudents($term, (int) $request->integer('limit', 25))),
        ], 'Students retrieved.');
    }

    /**
     * Aggregate enrollment statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Enrollment::class);

        return $this->success(
            $this->service->statistics($request->only(['academic_year_id', 'campus_id'])),
            'Statistics retrieved.'
        );
    }

    /**
     * Export the current filtered query as a CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Enrollment::class);

        $records = $this->service->export($request);

        $filename = 'enrollments-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Enrollment No.', 'Reference No.', 'Student', 'LRN', 'Academic Year', 'Term', 'Campus', 'Grade Level', 'Section', 'Type', 'Status', 'Enrolled']);

            foreach ($records as $enrollment) {
                fputcsv($stream, [
                    $enrollment->enrollment_number,
                    $enrollment->reference_number,
                    $enrollment->student?->full_name,
                    $enrollment->student?->lrn,
                    $enrollment->academicYear?->name,
                    $enrollment->academicTerm?->name,
                    $enrollment->campus?->name,
                    $enrollment->gradeLevel?->name,
                    $enrollment->section?->name,
                    $enrollment->enrollment_type,
                    $enrollment->status,
                    $enrollment->date_enrolled?->toDateString(),
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk-create draft enrollments from an array of rows or CSV file.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Enrollment::class);

        $rows = $request->input('rows');

        if ($request->hasFile('file')) {
            $rows = $this->parseCsv($request->file('file')->getRealPath());
        }

        if (! is_array($rows) || $rows === []) {
            return $this->error('No importable rows were provided.', null, 422);
        }

        $created = 0;
        $failed = [];

        foreach ($rows as $row) {
            $normalized = [];
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $value = trim($value) === '' ? null : trim($value);
                }
                $normalized[Str::snake($key)] = $value;
            }

            $validator = \Illuminate\Support\Facades\Validator::make($normalized, [
                'student_id' => ['required', 'integer', 'exists:students,id'],
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'campus_id' => ['required', 'integer', 'exists:campuses,id'],
                'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
                'enrollment_type' => ['nullable', 'string', 'max:80'],
            ]);

            if ($validator->fails()) {
                $failed[] = ['row' => $normalized, 'errors' => $validator->errors()->toArray()];
                continue;
            }

            try {
                $this->service->create($validator->validated());
                $created++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $normalized, 'errors' => ['_exception' => [$e->getMessage()]]];
            }
        }

        return $this->success([
            'created' => $created,
            'failed' => $failed,
            'total' => count($rows),
        ], 'Import finished.');
    }

    /**
     * The configurable enrollment types and statuses.
     */
    public function config(): JsonResponse
    {
        $this->authorize('viewAny', Enrollment::class);

        return $this->success([
            'types' => $this->service->enrollmentTypes(),
            'statuses' => $this->service->enrollmentStatuses(),
        ], 'Enrollment configuration retrieved.');
    }

    /**
     * Submit the draft enrollment (Draft -> For Principal Approval).
     */
    public function submit(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('update', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->submit($enrollment)),
            'Enrollment submitted.'
        );
    }

    /**
     * Forward a Pending public application to the principal.
     */
    public function forwardToPrincipal(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('update', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->forwardToPrincipal($enrollment)),
            'Application forwarded to the principal.'
        );
    }

    /**
     * Principal approves the enrollment (For Principal Approval -> Registrar Review).
     */
    public function principalApprove(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('principalApprove', $enrollment);

        $data = $request->only([
            'grade_level_id',
            'section_id',
            'academic_term_id',
            'curriculum_program_id',
            'program_cluster',
            'capacity_override_reason',
        ]);

        return $this->success(
            new EnrollmentResource($this->service->principalApprove($enrollment, $data)),
            'Learner assigned to a section and officially enrolled.'
        );
    }

    /**
     * Registrar reviews and places the enrollment (Registrar Review -> For Payment).
     */
    public function registrarReview(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('registrarReview', $enrollment);

        $data = $request->only([
            'academic_term_id',
            'grade_level_id',
            'section_id',
            'curriculum_program_id',
            'program_cluster',
            'elective_selections',
            'department',
            'strand',
            'track',
            'capacity_override_reason',
            'capacity_override',
        ]);

        return $this->success(
            new EnrollmentResource($this->service->registrarReview($enrollment, $data)),
            'Enrollment reviewed by the registrar.'
        );
    }

    /**
     * Accounting records the payment (For Payment -> For Final Check).
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('recordPayment', $enrollment);

        $data = $request->only([
            'payment_status',
            'payment_method',
            'down_payment',
            'payment_schedule_date',
            'payment_schedule_details',
        ]);

        return $this->success(
            new EnrollmentResource($this->service->recordPayment($enrollment, $data)),
            'Payment recorded.'
        );
    }

    /**
     * Registrar final check and official enrollment (For Final Check -> Officially Enrolled).
     */
    public function finalCheck(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('finalCheck', $enrollment);

        $date = $request->filled('date_enrolled') ? Carbon::parse($request->string('date_enrolled'))->toDateString() : null;

        $override = $request->filled('capacity_override_reason')
            ? $request->string('capacity_override_reason')->toString()
            : null;

        return $this->success(
            new EnrollmentResource($this->service->finalCheck($enrollment, $date, $override)),
            'Enrollment finalized.'
        );
    }

    /**
     * Verify the enrollment requirements.
     */
    public function verify(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('verify', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->verify($enrollment)),
            'Enrollment verification status updated.'
        );
    }

    /**
     * Approve an enrollment.
     */
    public function approve(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('approve', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->approve($enrollment)),
            'Enrollment approved.'
        );
    }

    /**
     * Reject an enrollment with a reason.
     */
    public function reject(ActionReasonRequest $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('reject', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->reject($enrollment, (string) $request->string('reason'))),
            'Enrollment rejected.'
        );
    }

    /**
     * Officially enroll an approved enrollment.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('complete', $enrollment);

        $date = $request->filled('date_enrolled') ? Carbon::parse($request->string('date_enrolled'))->toDateString() : null;

        $override = $request->filled('capacity_override_reason')
            ? $request->string('capacity_override_reason')->toString()
            : null;

        return $this->success(
            new EnrollmentResource($this->service->enroll($enrollment, $date, $override)),
            'Enrollment completed.'
        );
    }

    /**
     * Revert an officially enrolled enrollment back to Approved.
     */
    public function uncomplete(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('update', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->revert($enrollment)),
            'Enrollment reverted to Approved.'
        );
    }

    /**
     * Withdraw an enrollment.
     */
    public function withdraw(ActionReasonRequest $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('withdraw', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->withdraw($enrollment, (string) $request->string('reason'))),
            'Enrollment withdrawn.'
        );
    }

    /**
     * Cancel an early-stage enrollment.
     */
    public function cancel(ActionReasonRequest $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('cancel', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->cancel($enrollment, (string) $request->string('reason'))),
            'Enrollment cancelled.'
        );
    }

    /**
     * Process a transfer for an enrolled student.
     */
    public function transfer(EnrollmentTransferRequest $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('transfer', $enrollment);

        $enrollment = $this->service->transfer($enrollment, $request->validated());

        return $this->success(new EnrollmentResource($enrollment), 'Enrollment transferred.');
    }

    /**
     * Override the section capacity for this enrollment.
     */
    public function overrideCapacity(ActionReasonRequest $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('overrideCapacity', $enrollment);

        return $this->success(
            new EnrollmentResource($this->service->overrideCapacity($enrollment, (string) $request->string('reason'))),
            'Capacity override recorded.'
        );
    }

    /**
     * List the requirement items of an enrollment.
     */
    public function requirements(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('viewRequirements', $enrollment);

        /** @var EnrollmentRequirementService $requirementService */
        $requirementService = app(EnrollmentRequirementService::class);

        return $this->success([
            'items' => EnrollmentRequirementItemResource::collection($requirementService->itemsFor($enrollment)),
            'progress' => $requirementService->progress($enrollment),
        ], 'Enrollment requirements retrieved.');
    }

    /**
     * Re-sync the requirements of an enrollment with the active catalog.
     */
    public function syncRequirements(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('manageRequirements', $enrollment);

        /** @var EnrollmentRequirementService $requirementService */
        $requirementService = app(EnrollmentRequirementService::class);
        $requirementService->syncFor($enrollment, request()->boolean('reset_statuses'));

        return $this->success([
            'items' => EnrollmentRequirementItemResource::collection($requirementService->itemsFor($enrollment)),
            'progress' => $requirementService->progress($enrollment),
        ], 'Enrollment requirements synced.');
    }

    /**
     * Update the status of a single requirement item.
     */
    public function updateRequirementItem(EnrollmentRequirementItemRequest $request, int $id, int $itemId): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('manageRequirements', $enrollment);

        /** @var EnrollmentRequirementService $requirementService */
        $requirementService = app(EnrollmentRequirementService::class);

        return $this->success(
            new EnrollmentRequirementItemResource($requirementService->updateItem($enrollment, $itemId, $request->validated())),
            'Enrollment requirement updated.'
        );
    }

    /**
     * The activity + transfer history of an enrollment.
     */
    public function history(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('view', $enrollment);

        $activities = $enrollment->activities()->orderByDesc('created_at')->paginate(20);

        return $this->success([
            'activities' => [
                'items' => ActivityResource::collection($activities->items()),
                'pagination' => [
                    'current_page' => $activities->currentPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'last_page' => $activities->lastPage(),
                ],
            ],
            'transfers' => EnrollmentTransferResource::collection(
                $enrollment->transfers()->with('processedBy')->orderByDesc('created_at')->get()
            ),
        ], 'Enrollment history retrieved.');
    }

    /**
     * The digital signatures captured for this enrollment.
     */
    public function signatures(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('viewSignatures', $enrollment);

        return $this->success([
            'items' => $enrollment->signatures()->orderByDesc('signed_at')->get(['id', 'role', 'signer_name', 'signature_data', 'signed_ip', 'signed_at']),
        ], 'Enrollment signatures retrieved.');
    }

    /**
     * Capture a digital signature for this enrollment.
     */
    public function storeSignature(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('sign', $enrollment);

        $request->validate([
            'role' => ['required', 'string', 'in:parent,registrar,approver'],
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string'],
            'signed_at' => ['nullable', 'date'],
        ]);

        $signature = $enrollment->signatures()->create([
            'role' => $request->string('role'),
            'signer_name' => $request->string('signer_name'),
            'signature_data' => $request->string('signature_data'),
            'signed_ip' => $request->ip(),
            'signed_at' => $request->filled('signed_at') ? Carbon::parse($request->string('signed_at')) : now(),
        ]);

        return $this->success($signature, 'Signature captured.', 201);
    }

    /**
     * Prepare the printable enrollment document (HTML).
     */
    public function print(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('print', $enrollment);

        return $this->success([
            'html' => view('printable.enrollment', ['enrollment' => $enrollment])->render(),
        ], 'Printable document prepared.');
    }

    /**
     * The transfer history of an enrollment.
     */
    public function transfers(int $id): JsonResponse
    {
        $enrollment = $this->service->find($id);

        $this->authorize('view', $enrollment);

        return $this->success([
            'items' => EnrollmentTransferResource::collection(
                $enrollment->transfers()->with('processedBy')->orderByDesc('created_at')->get()
            ),
        ], 'Enrollment transfers retrieved.');
    }

    /**
     * Parse a CSV file into an array of associative rows.
     *
     * @return list<array<string, mixed>>
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null]) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $line[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}