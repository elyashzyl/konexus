<?php

namespace App\Services;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class AnnouncementService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['title', 'content'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'title', 'content'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['author'];

    public function __construct(private readonly AnnouncementRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Stamp the publish timestamp whenever an announcement is published.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->normalizePublishing($data);

        return parent::create($data);
    }

    /**
     * Stamp the publish timestamp whenever an announcement is published.
     *
     * @param  Announcement  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $this->normalizePublishing($data);

        return parent::update($model, $data);
    }

    /**
     * Set published_at when the announcement is marked as published and derive
     * the workflow status from the publishing flags.
     *
     * @param  array<string, mixed>  $data
     */
    private function normalizePublishing(array &$data): void
    {
        $scheduled = ! empty($data['scheduled_at']);

        if (($data['published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (isset($data['published']) && ! $data['published']) {
            $data['published_at'] = null;
        }

        if (! empty($data['published']) && ! $scheduled) {
            $data['status'] = 'published';
        } elseif ($scheduled && empty($data['published'])) {
            $data['status'] = 'scheduled';
        } elseif (! isset($data['status'])) {
            $data['status'] = 'draft';
        }
    }

    /**
     * The announcements currently visible to the given audience signature,
     * including future-dated scheduled announcements for staff previews.
     *
     * @param  array<string, mixed>  $signature
     * @return \Illuminate\Database\Eloquent\Collection<int, Announcement>
     */
    public function visibleFor(array $signature): \Illuminate\Database\Eloquent\Collection
    {
        return Announcement::query()
            ->with('author')
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (Announcement $announcement) => $announcement->isVisible() && $announcement->matchesAudience($signature))
            ->values();
    }
}
