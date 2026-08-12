<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\RequirementItemStatus;
use App\Exceptions\ApiException;
use App\Models\Enrollment;
use App\Models\EnrollmentRequirement;
use App\Repositories\Contracts\EnrollmentRequirementItemRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRequirementRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EnrollmentRequirementService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code', 'description'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code', 'sort_order', 'is_active'];

    /**
     * @var list<string>
     */
    protected array $with = [];

    protected string $defaultSortBy = 'sort_order';

    public function __construct(
        private readonly EnrollmentRequirementRepositoryInterface $repo,
        private readonly EnrollmentRequirementItemRepositoryInterface $itemRepo,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a new requirement definition.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['code'] = $data['code'] ?? $this->generateCode($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? ((int) $this->repo->query()->max('sort_order') + 1);

        return $this->repo->create($data);
    }

    /**
     * Update a requirement definition.
     *
     * @param  EnrollmentRequirement  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        if ($model->is_system ?? false) {
            throw ApiException::unprocessable('System requirements cannot be edited.');
        }

        if (isset($data['code']) && trim((string) $data['code']) === '') {
            unset($data['code']);
        }

        return $this->repo->update($model, $data);
    }

    /**
     * Possibly remove a requirement definition.
     *
     * @param  EnrollmentRequirement  $model
     */
    public function delete(Model $model): bool
    {
        $itemCount = $model->items()->count();

        if ($itemCount > 0) {
            throw ApiException::unprocessable('This requirement is already attached to enrollments and cannot be deleted; deactivate it instead.');
        }

        return $this->repo->delete($model);
    }

    /**
     * List the requirement items of one enrollment.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\EnrollmentRequirementItem>
     */
    public function itemsFor(Enrollment $enrollment): \Illuminate\Database\Eloquent\Collection
    {
        return $enrollment->requirementItems()
            ->with(['requirement', 'documents', 'verifiedBy', 'rejectedBy'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Sync (add/remove) the requirement items of an enrollment to match the
     * currently applicable requirement catalog.
     */
    public function syncFor(Enrollment $enrollment, bool $resetStatuses = false): void
    {
        $service = app(EnrollmentService::class);
        $service->syncRequirements($enrollment);

        if ($resetStatuses) {
            $enrollment->requirementItems()->update(['status' => RequirementItemStatus::NOT_SUBMITTED->value, 'remarks' => null]);
        }
    }

    /**
     * Update the status/remarks of a single requirement item of an enrollment.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(Enrollment $enrollment, int $itemId, array $data): Model
    {
        $item = $enrollment->requirementItems()->findOrFail($itemId);

        $status = $data['status'] ?? $item->status;

        $attributes = [
            'status' => $status,
            'remarks' => $data['remarks'] ?? $item->remarks,
        ];

        if ($status === RequirementItemStatus::VERIFIED->value) {
            $attributes['verified_by'] = auth()->id();
            $attributes['verified_at'] = now();
            $attributes['rejected_by'] = null;
            $attributes['rejected_at'] = null;
        }

        if ($status === RequirementItemStatus::REJECTED->value) {
            $attributes['rejected_by'] = auth()->id();
            $attributes['rejected_at'] = now();
            $attributes['verified_by'] = null;
            $attributes['verified_at'] = null;
        }

        if ($status === RequirementItemStatus::SUBMITTED->value || $status === RequirementItemStatus::NOT_SUBMITTED->value) {
            $attributes['verified_by'] = null;
            $attributes['verified_at'] = null;
            $attributes['rejected_by'] = null;
            $attributes['rejected_at'] = null;
        }

        return $this->itemRepo->update($item, $attributes)->load(['requirement', 'documents', 'verifiedBy', 'rejectedBy']);
    }

    /**
     * Compute the verification progress for an enrollment.
     *
     * @return array<string, mixed>
     */
    public function progress(Enrollment $enrollment): array
    {
        $items = $enrollment->requirementItems()->with('requirement')->get();

        $required = $items->filter(fn ($item) => (bool) $item->requirement?->is_required);
        $satisfied = $required->filter(
            fn ($item) => in_array($item->status, RequirementItemStatus::satisfiedStatuses(), true)
        );

        return [
            'total' => $items->count(),
            'required' => $required->count(),
            'satisfied' => $satisfied->count(),
            'complete' => $required->count() > 0 && $satisfied->count() === $required->count(),
            'can_be_officially_enrolled' => in_array($enrollment->status, [
                EnrollmentStatus::APPROVED->value,
                EnrollmentStatus::FOR_APPROVAL->value,
            ], true) && $required->count() === $satisfied->count(),
        ];
    }

    /**
     * Generate a unique requirement code from the given name.
     */
    protected function generateCode(string $name): string
    {
        $base = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($name));
        $code = $base;
        $index = 1;

        while ($this->repo->query()->where('code', $code)->exists()) {
            $code = substr($base, 0, 8).'-'.$index;
            $index++;
        }

        return $code;
    }
}