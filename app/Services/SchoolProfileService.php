<?php

namespace App\Services;

use App\Models\SchoolProfile;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SchoolProfileRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class SchoolProfileService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'short_name', 'school_id', 'region', 'division', 'district', 'address'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'short_name', 'school_id', 'region', 'division', 'district', 'address'];

    public function __construct(private readonly SchoolProfileRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a school profile and keep the active/primary flag singular.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $profile = parent::create($data);

        $this->enforceSingularFlags($profile);

        return $profile;
    }

    /**
     * Update a school profile and keep the active/primary flag singular.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $profile = parent::update($model, $data);

        $this->enforceSingularFlags($profile);

        return $profile;
    }

    /**
     * Only one school profile can be active and only one can be primary.
     */
    private function enforceSingularFlags(SchoolProfile $profile): void
    {
        if ($profile->is_active) {
            SchoolProfile::query()
                ->whereKeyNot($profile->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        if ($profile->is_primary) {
            SchoolProfile::query()
                ->whereKeyNot($profile->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }
    }
}
