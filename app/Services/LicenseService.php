<?php

namespace App\Services;

use App\Enums\Platform\LicenseStatus;
use App\Enums\Platform\SubscriptionHistoryAction;
use App\Exceptions\ApiException;
use App\Models\License;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Repositories\Contracts\LicenseRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Manages tenant license keys. Keys are stored encrypted and are only ever
 * surfaced as masked values (KONX-****-****-A82F); the full key is available
 * exclusively to authorized platform administrators through an explicit
 * reveal action.
 */
class LicenseService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['license_key'];

    /**
     * Relation columns included in free-text search.
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = ['tenant' => ['name', 'code']];

    protected array $sortable = ['id', 'issued_date', 'start_date', 'expiration_date', 'status', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['tenant', 'plan'];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(
        private readonly LicenseRepositoryInterface $repo,
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

        foreach (['status', 'tenant_id', 'plan_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Issue a new license for a tenant and plan.
     *
     * @param  array<string, mixed>  $data
     */
    public function issueLicense(Tenant $tenant, SubscriptionPlan $plan, array $data = []): License
    {
        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::today();
        $expiration = isset($data['expiration_date'])
            ? Carbon::parse($data['expiration_date'])
            : $start->copy()->addYear();

        $license = License::query()->create([
            'license_key' => $this->generateKey(),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'issued_date' => Carbon::today()->toDateString(),
            'start_date' => $start->toDateString(),
            'expiration_date' => $expiration->toDateString(),
            'status' => LicenseStatus::ACTIVE->value,
            'max_users' => $plan->max_users,
            'max_students' => $plan->max_students,
            'max_branches' => $plan->max_branches,
            'max_storage_mb' => $plan->max_storage_mb,
            'features' => $data['features'] ?? $plan->featureCodes(),
            'created_by' => auth()->id(),
        ]);

        $this->audit->record($tenant, SubscriptionHistoryAction::LICENSE_CREATED, [
            'subscription_id' => $data['subscription_id'] ?? null,
            'description' => "License issued for plan {$plan->name}.",
            'new_value' => [
                'license_id' => $license->id,
                'plan_id' => $plan->id,
                'expiration_date' => $license->expiration_date?->toDateString(),
            ],
        ]);

        return $license;
    }

    /**
     * Create a license record directly from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['license_key'] = $data['license_key'] ?? $this->generateKey();
        $data['issued_date'] = $data['issued_date'] ?? Carbon::today()->toDateString();
        $data['start_date'] = $data['start_date'] ?? Carbon::today()->toDateString();
        $data['status'] = $data['status'] ?? LicenseStatus::ACTIVE->value;

        $license = parent::create($data);

        $this->audit->record(License::find($license->id)->tenant, SubscriptionHistoryAction::LICENSE_CREATED, [
            'description' => 'License created.',
            'new_value' => ['license_id' => $license->id],
        ]);

        return $license;
    }

    /**
     * Generate a fresh key, invalidating the previous one. Only the newest
     * key for the tenant is active.
     */
    public function regenerate(License $license): License
    {
        $license->update([
            'license_key' => $this->generateKey(),
            'issued_date' => Carbon::today()->toDateString(),
            'updated_by' => auth()->id(),
        ]);

        $this->audit->record($license->tenant, SubscriptionHistoryAction::LICENSE_REGENERATED, [
            'description' => 'License key regenerated.',
            'new_value' => ['license_id' => $license->id],
        ]);

        return $license;
    }

    /**
     * Revoke a license.
     *
     * @param  array<string, mixed>  $data
     */
    public function revoke(License $license, array $data = []): License
    {
        if ($license->status === LicenseStatus::REVOKED->value) {
            throw ApiException::conflict('The license is already revoked.');
        }

        $license->update(['status' => LicenseStatus::REVOKED->value]);

        $this->audit->record($license->tenant, SubscriptionHistoryAction::CANCELLED, [
            'description' => 'License revoked.',
            'reason' => $data['reason'] ?? null,
            'new_value' => ['license_id' => $license->id, 'status' => LicenseStatus::REVOKED->value],
        ]);

        return $license;
    }

    /**
     * Generate a secure KONX-XXXX-XXXX-XXXX license key.
     */
    public function generateKey(): string
    {
        $segment = fn (): string => strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        return 'KONX-'.$segment().'-'.$segment().'-'.$segment();
    }
}