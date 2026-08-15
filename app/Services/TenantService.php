<?php

namespace App\Services;

use App\Enums\Platform\SubscriptionHistoryAction;
use App\Enums\Platform\TenantStatus;
use App\Exceptions\ApiException;
use App\Models\SchoolProfile;
use App\Models\Tenant;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Manages school/organization tenants and their lifecycle status.
 */
class TenantService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code'];

    protected array $sortable = ['id', 'name', 'code', 'status', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['schoolProfile'];

    protected string $defaultSortBy = 'name';

    public function __construct(
        private readonly TenantRepositoryInterface $repo,
        private readonly SubscriptionAuditService $audit,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The equality filters extracted from the request.
     *
     * @return array<string, mixed>
     */
    protected function filters(\App\Http\Requests\Api\IndexRequest $request): array
    {
        $filters = parent::filters($request);

        foreach (['status', 'school_profile_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a tenant and record the audit event.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['code'] = $data['code'] ?? $this->generateCode();
        $data['status'] = $data['status'] ?? TenantStatus::ACTIVE->value;

        $tenant = parent::create($data);

        $this->audit->record($tenant, SubscriptionHistoryAction::CREATED, [
            'description' => "Tenant {$tenant->name} was registered.",
            'new_value' => ['name' => $tenant->name, 'code' => $tenant->code],
        ]);

        return $tenant;
    }

    /**
     * Update a tenant while capturing changes in the audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $old = ['name' => $model->name, 'status' => $model->status];
        $tenant = parent::update($model, $data);

        if ($old['name'] !== $tenant->name || $old['status'] !== $tenant->status) {
            $this->audit->record($tenant, SubscriptionHistoryAction::TENANT_UPDATED, [
                'description' => "Tenant {$tenant->name} was updated.",
                'old_value' => $old,
                'new_value' => ['name' => $tenant->name, 'status' => $tenant->status],
            ]);
        }

        return $tenant;
    }

    /**
     * Suspend a tenant and all of its active subscriptions.
     */
    public function suspend(Tenant $tenant, ?string $reason = null): Tenant
    {
        if ($tenant->status === TenantStatus::SUSPENDED->value) {
            throw ApiException::conflict('The tenant is already suspended.');
        }

        $tenant->update(['status' => TenantStatus::SUSPENDED->value]);

        $tenant->subscriptions()
            ->whereIn('status', ['trial', 'active', 'grace_period', 'past_due'])
            ->update(['status' => 'suspended']);

        $this->audit->record($tenant, SubscriptionHistoryAction::SUSPENDED, [
            'description' => "Tenant {$tenant->name} was suspended.",
            'reason' => $reason,
            'new_value' => ['status' => TenantStatus::SUSPENDED->value],
        ]);

        return $tenant;
    }

    /**
     * Reactivate a suspended tenant.
     */
    public function resume(Tenant $tenant, ?string $reason = null): Tenant
    {
        if ($tenant->status !== TenantStatus::SUSPENDED->value) {
            throw ApiException::conflict('The tenant is not suspended.');
        }

        $tenant->update(['status' => TenantStatus::ACTIVE->value]);

        $this->audit->record($tenant, SubscriptionHistoryAction::RESUMED, [
            'description' => "Tenant {$tenant->name} was reactivated.",
            'reason' => $reason,
            'new_value' => ['status' => TenantStatus::ACTIVE->value],
        ]);

        return $tenant;
    }

    /**
     * Generate a unique, human-friendly tenant code.
     */
    public function generateCode(): string
    {
        do {
            $code = 'TEN-'.strtoupper(uniqid());
        } while (Tenant::query()->withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /**
     * The tenant representing a school, creating it on first use.
     *
     * A school is the primary organization key; its billing/licensing tenant is
     * derived from it on demand so subscriptions can be granted per school
     * without a separate tenant management step.
     */
    public function resolveForSchool(SchoolProfile $school): Tenant
    {
        $tenant = Tenant::query()->where('school_profile_id', $school->id)->first();

        if (! $tenant) {
            $tenant = $this->create([
                'school_profile_id' => $school->id,
                'code' => $this->schoolCode($school),
                'name' => $school->name,
                'status' => TenantStatus::ACTIVE->value,
            ]);
        }

        return $tenant;
    }

    /**
     * A unique tenant code derived from the school's short name.
     */
    private function schoolCode(SchoolProfile $school): string
    {
        $base = $school->short_name
            ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $school->short_name))
            : 'SCHOOL';

        $code = $base;
        $suffix = 1;

        while (Tenant::query()->withTrashed()->where('code', $code)->exists()) {
            $code = $base.'-'.($suffix++);
        }

        return $code;
    }
}