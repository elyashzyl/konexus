<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\EnrollmentDocumentUploadRequest;
use App\Http\Resources\EnrollmentDocumentResource;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Services\EnrollmentDocumentService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class EnrollmentDocumentController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(
        private EnrollmentDocumentService $documentService,
        private EnrollmentService $enrollmentService,
    ) {}

    /**
     * Guard access to an enrollment and return its model.
     */
    private function resolveEnrollment(int $id, string $ability): Enrollment
    {
        $enrollment = $this->enrollmentService->find($id);

        $this->authorize($ability, $enrollment);

        return $enrollment;
    }

    /**
     * List the documents attached to an enrollment.
     */
    public function index(int $enrollmentId): \Illuminate\Http\JsonResponse
    {
        $enrollment = $this->resolveEnrollment($enrollmentId, 'viewDocuments');

        return $this->success([
            'items' => EnrollmentDocumentResource::collection($this->documentService->forEnrollment($enrollment)),
        ], 'Documents retrieved.');
    }

    /**
     * Upload a document against an enrollment.
     */
    public function store(EnrollmentDocumentUploadRequest $request, int $enrollmentId): \Illuminate\Http\JsonResponse
    {
        $enrollment = $this->resolveEnrollment($enrollmentId, 'uploadDocuments');

        $document = $this->documentService->store(
            $enrollment,
            $request->file('file'),
            $request->filled('requirement_item_id') ? (int) $request->integer('requirement_item_id') : null,
            (string) $request->string('name'),
        );

        return $this->success(new EnrollmentDocumentResource($document), 'Document uploaded.', 201);
    }

    /**
     * Download an enrollment document (authorized stream).
     */
    public function download(int $enrollmentId, int $documentId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $enrollment = $this->resolveEnrollment($enrollmentId, 'viewDocuments');

        $document = $this->documentService->find($documentId);

        if ($document->enrollment_id !== $enrollment->id) {
            abort(404);
        }

        return $this->documentService->download($document);
    }

    /**
     * Remove a document from an enrollment.
     */
    public function destroy(int $enrollmentId, int $documentId): \Illuminate\Http\JsonResponse
    {
        $enrollment = $this->resolveEnrollment($enrollmentId, 'deleteDocuments');

        $document = $this->documentService->find($documentId);

        if ($document->enrollment_id !== $enrollment->id) {
            abort(404);
        }

        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return $this->success(null, 'Document deleted.');
    }

    /**
     * Stream (preview) a document for the signed-in user.
     */
    public function preview(int $enrollmentId, int $documentId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $enrollment = $this->resolveEnrollment($enrollmentId, 'viewDocuments');

        $document = $this->documentService->find($documentId);

        if ($document->enrollment_id !== $enrollment->id) {
            abort(404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk(EnrollmentDocument::storageDisk());

        if (! $disk->exists($document->file_path)) {
            abort(404);
        }

        return $disk->response($document->file_path, $document->name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
        ]);
    }
}