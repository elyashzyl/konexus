<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentRequirementItem;
use App\Repositories\Contracts\EnrollmentDocumentRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentDocumentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name'];

    /**
     * @var list<string>
     */
    protected array $with = ['enrollment', 'requirementItem', 'uploadedBy'];

    public function __construct(private readonly EnrollmentDocumentRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The documents attached to an enrollment.
     *
     * @return Collection<int, EnrollmentDocument>
     */
    public function forEnrollment(Enrollment $enrollment): Collection
    {
        return $enrollment->documents()
            ->with(['requirementItem', 'uploadedBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Store an uploaded document against an enrollment.
     */
    public function store(Enrollment $enrollment, UploadedFile $file, ?int $requirementItemId = null, string $name = ''): EnrollmentDocument
    {
        $requirementItem = null;

        if ($requirementItemId) {
            $requirementItem = $enrollment->requirementItems()->find($requirementItemId);

            if (! $requirementItem) {
                throw ApiException::unprocessable('The selected requirement does not belong to this enrollment.');
            }
        }

        $safeName = $name !== ''
            ? trim($name)
            : $file->getClientOriginalName();

        $path = $file->store('enrollments/'.$enrollment->id, EnrollmentDocument::storageDisk());

        return $this->repo->create([
            'enrollment_id' => $enrollment->id,
            'requirement_item_id' => $requirementItem?->id,
            'name' => $safeName,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'is_active' => true,
        ]);
    }

    /**
     * Stream a stored document for download.
     */
    public function download(EnrollmentDocument $document): StreamedResponse
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(EnrollmentDocument::storageDisk());

        if (! $disk->exists($document->file_path)) {
            throw ApiException::notFound('The requested file is no longer available.');
        }

        return $disk->download($document->file_path, $document->name);
    }

    /**
     * Remove a document and the physical file.
     *
     * @param  EnrollmentDocument  $model
     */
    public function delete(Model $model): bool
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(EnrollmentDocument::storageDisk());

        if ($disk->exists($model->file_path)) {
            $disk->delete($model->file_path);
        }

        return $this->repo->delete($model);
    }
}