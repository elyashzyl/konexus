<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EnrollmentStatus;
use App\Http\Requests\Api\PublicEnrollmentDetailsRequest;
use App\Http\Requests\Api\PublicEnrollmentFamilyRequest;
use App\Http\Requests\Api\PublicEnrollmentRequest;
use App\Http\Requests\Api\PublicEnrollmentStudentRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\ParentGuardian;
use App\Models\SchoolProfile;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Handles the public (unauthenticated) online enrollment application.
 *
 * The application is collected in parts:
 *   - Part 1 – Application details (program, school year, contact).
 *   - Part 2 – Student information (creates and links a Student record).
 *   - Part 3 – Family background (father / mother / guardian records).
 *
 * Submissions are stored as pending enrollments and are automatically purged
 * when the application is not pursued (see the PurgeAbandonedEnrollments
 * command).
 */
class PublicEnrollmentController extends ApiController
{
    /**
     * How long an unfinished application is kept before it is auto-deleted.
     */
    public const APPLICATION_RETENTION_DAYS = 30;

    /**
     * The option lists needed to render the online enrollment form.
     */
    public function options(): JsonResponse
    {
        $schoolId = SchoolProfile::query()->where('is_active', true)->orderBy('id')->value('id');

        $academicYears = AcademicYear::query()
            ->when($schoolId, fn ($query) => $query->where('school_profile_id', $schoolId))
            ->orderByDesc('start_date')
            ->limit(10)
            ->get(['id', 'name', 'code', 'start_date', 'end_date']);

        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($query) => $query->where('school_profile_id', $schoolId))
            ->orderBy('sequence')
            ->get(['id', 'name', 'code', 'short_name', 'education_level']);

        // Fall back to the platform structure when the active school has not
        // configured its own academic year / grade levels yet.
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::query()
                ->orderByDesc('start_date')
                ->limit(10)
                ->get(['id', 'name', 'code', 'start_date', 'end_date']);
        }

        if ($gradeLevels->isEmpty()) {
            $gradeLevels = GradeLevel::query()
                ->orderBy('sequence')
                ->get(['id', 'name', 'code', 'short_name', 'education_level']);
        }

        return $this->success([
            'school_id' => $schoolId,
            'academic_years' => $academicYears,
            'grade_levels' => $gradeLevels,
        ], 'Enrollment options retrieved.');
    }

    /**
     * Store a Part 1 online enrollment application.
     */
    public function store(PublicEnrollmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $seq = ((int) Enrollment::withTrashed()->max('id')) + 1;
        $yearCode = (string) (AcademicYear::query()->find($data['academic_year_id'])?->code ?? now()->year);
        $padded = str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

        $enrollment = Enrollment::create([
            'academic_year_id' => $data['academic_year_id'],
            'department' => $data['department'],
            'strand' => $data['strand'] ?? null,
            'track' => $data['track'],
            'incoming_level' => $data['incoming_level'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'],
            'enrollment_number' => 'ENR-'.$yearCode.'-'.$padded,
            'reference_number' => 'KXN-EN-'.$yearCode.'-'.$padded,
            'status' => EnrollmentStatus::PENDING->value,
            'enrollment_type' => $data['status'],
            'enrollment_date' => now()->toDateString(),
            'application_submitted_at' => now(),
            'application_expires_at' => now()->addDays(self::APPLICATION_RETENTION_DAYS),
        ]);

        return $this->success([
            'id' => $enrollment->id,
            'reference_number' => $enrollment->reference_number,
            'status' => $enrollment->status,
            'expires_at' => $enrollment->application_expires_at?->toISOString(),
        ], 'Enrollment application submitted.', 201);
    }

    /**
     * Retrieve an application with its student and family information (used to
     * resume an in-progress enrollment).
     */
    public function show(Enrollment $enrollment): JsonResponse
    {
        return $this->success([
            'application' => [
                'id' => $enrollment->id,
                'reference_number' => $enrollment->reference_number,
                'status' => $enrollment->status,
                'academic_year_id' => $enrollment->academic_year_id,
                'department' => $enrollment->department,
                'strand' => $enrollment->strand,
                'track' => $enrollment->track,
                'incoming_level' => $enrollment->incoming_level,
                'email' => $enrollment->email,
                'mobile_number' => $enrollment->mobile_number,
                'application_expires_at' => $enrollment->application_expires_at?->toISOString(),
            ],
            'student' => $enrollment->student ? $this->studentPayload($enrollment->student) : null,
            'family' => $this->familyPayload($enrollment),
            'siblings' => $enrollment->siblings ?? [],
            'tuition_plan' => $enrollment->tuition_plan,
            'medical_history' => $enrollment->medical_history,
            'chinese_details' => $enrollment->chinese_details,
            'agreement' => [
                'photo_consent' => $enrollment->photo_consent,
                'registration_consent' => $enrollment->registration_consent,
                'credentialing_consent' => $enrollment->credentialing_consent,
                'rules_consent' => $enrollment->rules_consent,
                'date_of_registration' => $enrollment->date_of_registration?->toDateString(),
                'initial_payment' => $enrollment->initial_payment,
            ],
            'signatures' => $enrollment->signatures()
                ->get(['role', 'signer_name', 'signature_data', 'signed_at'])
                ->map(fn ($signature) => [
                    'role' => $signature->role,
                    'signer_name' => $signature->signer_name,
                    'signature_data' => $signature->signature_data,
                    'signed_at' => $signature->signed_at?->toIso8601String(),
                ]),
        ], 'Enrollment application retrieved.');
    }

    /**
     * Store or update the Part 2 student information and link the student to
     * the application.
     */
    public function storeStudent(Enrollment $enrollment, PublicEnrollmentStudentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $student = $enrollment->student;

        if ($student === null) {
            $student = Student::create([...$data, 'student_number' => $this->generateStudentNumber()]);
            $enrollment->forceFill(['student_id' => $student->id])->save();
        } else {
            $student->update($data);
        }

        return $this->success([
            'student_id' => $student->id,
            'student' => $this->studentPayload($student),
        ], 'Student information saved.', 200);
    }

    /**
     * Upload the student's 2x2 photo.
     */
    public function storeStudentPhoto(Enrollment $enrollment, Request $request): JsonResponse
    {
        $student = $enrollment->student;

        if ($student === null) {
            return $this->error('Complete the student information before uploading a photo.', null, 422);
        }

        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:4096',
                Rule::dimensions()->minWidth(300)->minHeight(300)->ratio('1/1'),
            ],
        ]);

        $path = $request->file('photo')->store('students/photos', 'public');
        $student->forceFill(['profile_picture_path' => $path])->save();

        return $this->success([
            'profile_picture_path' => $path,
            'profile_picture_url' => url('storage/'.$path),
        ], 'Photo uploaded.', 200);
    }

    /**
     * Store or update the Part 3 family background.
     */
    public function storeFamily(Enrollment $enrollment, PublicEnrollmentFamilyRequest $request): JsonResponse
    {
        $student = $enrollment->student;

        if ($student === null) {
            return $this->error('Complete the student information first.', null, 422);
        }

        $data = $request->validated();

        $father = isset($data['father']) ? $this->upsertParent($student, 'father', $data['father']) : null;
        $mother = isset($data['mother']) ? $this->upsertParent($student, 'mother', $data['mother']) : null;
        $guardian = isset($data['guardian']) ? $this->upsertGuardian($student, $data['guardian']) : null;

        if (array_key_exists('family_monthly_income', $data)) {
            $student->forceFill(['family_monthly_income' => $data['family_monthly_income']])->save();
        }

        if ($guardian !== null) {
            $student->forceFill([
                'emergency_contact_name' => $guardian->full_name,
                'emergency_contact_relationship' => $guardian->relationship,
                'emergency_contact_mobile' => $guardian->mobile_number,
            ])->save();
        }

        return $this->success([
            'student_id' => $student->id,
            'family' => $this->familyPayload($enrollment),
        ], 'Family background saved.', 200);
    }

    /**
     * Store or update the Parts 4-8 details (siblings, tuition plan, medical
     * history, Chinese class details, and agreements).
     */
    public function storeDetails(Enrollment $enrollment, PublicEnrollmentDetailsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $attributes = [];

        foreach (['siblings', 'tuition_plan', 'medical_history', 'chinese_details'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $data[$key];
            }
        }

        if (isset($data['agreement'])) {
            foreach (['photo_consent', 'registration_consent', 'credentialing_consent', 'rules_consent', 'date_of_registration', 'initial_payment'] as $key) {
                if (array_key_exists($key, $data['agreement'])) {
                    $attributes[$key] = $data['agreement'][$key];
                }
            }
        }

        if ($attributes !== []) {
            $enrollment->forceFill($attributes)->save();
        }

        return $this->success([
            'id' => $enrollment->id,
            'details' => [
                'siblings' => $enrollment->siblings ?? [],
                'tuition_plan' => $enrollment->tuition_plan,
                'medical_history' => $enrollment->medical_history,
                'chinese_details' => $enrollment->chinese_details,
                'agreement' => [
                    'photo_consent' => $enrollment->photo_consent,
                    'registration_consent' => $enrollment->registration_consent,
                    'credentialing_consent' => $enrollment->credentialing_consent,
                    'rules_consent' => $enrollment->rules_consent,
                    'date_of_registration' => $enrollment->date_of_registration?->toDateString(),
                    'initial_payment' => $enrollment->initial_payment,
                ],
            ],
        ], 'Application details saved.', 200);
    }

    /**
     * Capture a digital signature for the student or parent/guardian.
     */
    public function storeSignature(Enrollment $enrollment, Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['student', 'parent'])],
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string', 'max:3000000'],
        ]);

        $signature = $enrollment->signatures()->updateOrCreate(
            ['role' => $request->string('role')],
            [
                'signer_name' => $request->string('signer_name'),
                'signature_data' => $request->string('signature_data'),
                'signed_ip' => $request->ip(),
                'signed_at' => now(),
            ]
        );

        return $this->success([
            'role' => $signature->role,
            'signer_name' => $signature->signer_name,
            'signed_at' => $signature->signed_at?->toIso8601String(),
        ], 'Signature captured.', 200);
    }

    /**
     * Create or update the father/mother parent record for the student.
     *
     * @param  array<string, mixed>  $data
     */
    private function upsertParent(Student $student, string $relationship, array $data): ParentGuardian
    {
        $payload = [
            'first_name' => $data['first_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'mobile_number' => $data['mobile_number'] ?? null,
            'email' => $data['email'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'address' => $data['address'] ?? null,
            'not_applicable' => (bool) ($data['not_applicable'] ?? false),
            'is_active' => true,
        ];

        if ($relationship === 'mother' && isset($data['maiden_name'])) {
            $payload['maiden_name'] = $data['maiden_name'];
        }

        $parent = $student->parents()->where('relationship', $relationship)->first();

        if ($parent === null) {
            $parent = ParentGuardian::create([...$payload, 'relationship' => $relationship]);
            $student->parents()->attach($parent->id, ['is_primary' => 0]);
        } else {
            $parent->update($payload);
        }

        return $parent;
    }

    /**
     * Create or update the guardian record for the student.
     *
     * @param  array<string, mixed>  $data
     */
    private function upsertGuardian(Student $student, array $data): Guardian
    {
        $payload = [
            'first_name' => $data['first_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'relationship' => $data['relationship'] ?? null,
            'mobile_number' => $data['mobile_number'] ?? null,
            'address' => $data['address'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'is_active' => true,
        ];

        $guardian = $student->guardians()->first();

        if ($guardian === null) {
            $guardian = Guardian::create($payload);
            $student->guardians()->attach($guardian->id, ['is_primary' => 1]);
        } else {
            $guardian->update($payload);
        }

        return $guardian;
    }

    /**
     * Generate a unique, human-friendly student number.
     */
    private function generateStudentNumber(): string
    {
        do {
            $number = 'KXN-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (Student::query()->where('student_number', $number)->exists());

        return $number;
    }

    /**
     * Build the Part 2 student information payload.
     *
     * @return array<string, mixed>
     */
    private function studentPayload(Student $student): array
    {
        return [
            'id' => $student->id,
            'school_student_id' => $student->school_student_id,
            'lrn' => $student->lrn,
            'first_name' => $student->first_name,
            'middle_name' => $student->middle_name,
            'last_name' => $student->last_name,
            'extension_name' => $student->extension_name,
            'nickname' => $student->nickname,
            'birth_date' => $student->birth_date?->toDateString(),
            'age' => $student->age,
            'gender' => $student->gender,
            'citizenship' => $student->citizenship,
            'religion' => $student->religion,
            'mobile_number' => $student->mobile_number,
            'email' => $student->email,
            'place_of_birth' => $student->place_of_birth,
            'ethnicity' => $student->ethnicity,
            'is_indigenous' => (bool) $student->is_indigenous,
            'mother_tongue' => $student->mother_tongue,
            'telephone_number' => $student->telephone_number,
            'current_address' => $student->current_address,
            'current_province' => $student->current_province,
            'current_city' => $student->current_city,
            'current_barangay' => $student->current_barangay,
            'interests' => $student->interests ?? [],
            'profile_picture_url' => $student->profile_picture_path ? url('storage/'.$student->profile_picture_path) : null,
        ];
    }

    /**
     * Build the Part 3 family background payload.
     *
     * @return array<string, mixed>
     */
    private function familyPayload(Enrollment $enrollment): array
    {
        $student = $enrollment->student;

        if ($student === null) {
            return ['father' => null, 'mother' => null, 'guardian' => null, 'family_monthly_income' => null];
        }

        return [
            'father' => $this->parentPayload($student->parents()->where('relationship', 'father')->first()),
            'mother' => $this->parentPayload($student->parents()->where('relationship', 'mother')->first()),
            'guardian' => $this->guardianPayload($student->guardians()->first()),
            'family_monthly_income' => $student->family_monthly_income,
        ];
    }

    /**
     * Build a parent payload.
     *
     * @return array<string, mixed>|null
     */
    private function parentPayload(?ParentGuardian $parent): ?array
    {
        if ($parent === null) {
            return null;
        }

        return [
            'id' => $parent->id,
            'not_applicable' => (bool) $parent->not_applicable,
            'first_name' => $parent->first_name,
            'middle_name' => $parent->middle_name,
            'last_name' => $parent->last_name,
            'maiden_name' => $parent->maiden_name,
            'mobile_number' => $parent->mobile_number,
            'email' => $parent->email,
            'occupation' => $parent->occupation,
            'address' => $parent->address,
        ];
    }

    /**
     * Build a guardian payload.
     *
     * @return array<string, mixed>|null
     */
    private function guardianPayload(?Guardian $guardian): ?array
    {
        if ($guardian === null) {
            return null;
        }

        return [
            'id' => $guardian->id,
            'first_name' => $guardian->first_name,
            'middle_name' => $guardian->middle_name,
            'last_name' => $guardian->last_name,
            'relationship' => $guardian->relationship,
            'mobile_number' => $guardian->mobile_number,
            'address' => $guardian->address,
            'occupation' => $guardian->occupation,
        ];
    }
}