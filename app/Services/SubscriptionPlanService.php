<?php

namespace App\Services;

use App\Enums\Platform\SubscriptionFeature;
use App\Exceptions\ApiException;
use App\Models\SubscriptionPlan;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Manages the configurable subscription plans and the feature catalog each
 * plan grants.
 */
class SubscriptionPlanService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code', 'description'];

    protected array $sortable = ['id', 'name', 'code', 'monthly_price', 'annual_price', 'display_order', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['planFeatures'];

    protected string $defaultSortBy = 'display_order';

    public function __construct(private readonly SubscriptionPlanRepositoryInterface $repo) {}

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

        foreach (['is_active', 'billing_cycle'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a plan and attach its granted features.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $features = $this->extractFeatures($data);

        return DB::transaction(function () use ($data, $features): Model {
            $plan = parent::create($data);
            $this->syncFeatures($plan, $features);

            return $plan;
        });
    }

    /**
     * Update a plan and its granted features.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $features = $this->extractFeatures($data);

        return DB::transaction(function () use ($model, $data, $features): Model {
            $plan = parent::update($model, $data);
            $this->syncFeatures($plan, $features);

            return $plan;
        });
    }

    /**
     * The active plans ordered for display.
     *
     * @return \Illuminate\Support\Collection<int, SubscriptionPlan>
     */
    public function activePlans()
    {
        return $this->repo->query()
            ->with($this->with)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * The complete feature catalog.
     *
     * @return list<array{value: string, label: string}>
     */
    public function featureCatalog(): array
    {
        return SubscriptionFeature::toOptions();
    }

    /**
     * Extract the features array from the payload (always a list of codes).
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function extractFeatures(array &$data): array
    {
        $features = $data['features'] ?? [];

        unset($data['features']);

        if (! is_array($features)) {
            $features = [];
        }

        $valid = array_column(SubscriptionFeature::toOptions(), 'value');
        $features = array_values(array_intersect(array_map('strval', $features), $valid));

        foreach ($features as $code) {
            if (! in_array($code, $valid, true)) {
                throw ApiException::unprocessable("Unknown feature code: {$code}.");
            }
        }

        return $features;
    }

    /**
     * Replace the granted features of a plan.
     *
     * @param  list<string>  $features
     */
    protected function syncFeatures(SubscriptionPlan $plan, array $features): void
    {
        $plan->planFeatures()->delete();

        foreach ($features as $code) {
            $plan->planFeatures()->create(['feature_code' => $code]);
        }
    }
}