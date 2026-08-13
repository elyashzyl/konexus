<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The Notification Center API.
 *
 * Part 8 – Notification Center. Users can browse, filter, acknowledge and
 * configure the in-app notifications produced by every KONEXUS module.
 */
class NotificationController extends ApiController
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    /**
     * Paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $request->user())
            ->when($request->boolean('unread_only'), fn (Builder $q) => $q->whereNull('read_at'))
            ->when($request->filled('type'), fn (Builder $q) => $q->whereJsonContains('data->type', $request->string('type')->toString()))
            ->orderByDesc('created_at');

        $notifications = $query->paginate((int) $request->integer('per_page', 15));

        return $this->success([
            'items' => $notifications->through(fn (DatabaseNotification $n) => $this->shape($n))->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ], 'Notifications retrieved.');
    }

    /**
     * The number of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $request->user())
            ->whereNull('read_at')
            ->count();

        return $this->success(['unread' => $count], 'Unread notification count retrieved.');
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $request->user())
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->success($this->shape($notification->refresh()), 'Notification marked as read.');
    }

    /**
     * Mark every notification as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $request->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }

    /**
     * Delete the already-read notifications (history cleanup).
     */
    public function destroyRead(Request $request): JsonResponse
    {
        $deleted = DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $request->user())
            ->whereNotNull('read_at')
            ->delete();

        return $this->success(['deleted' => $deleted], 'Read notifications cleared.');
    }

    /**
     * The channel/category preference matrix of the authenticated user.
     */
    public function preferences(Request $request): JsonResponse
    {
        return $this->success([
            'categories' => NotificationService::CATEGORIES,
            'channels' => NotificationService::CHANNELS,
            'matrix' => $this->service->preferences($request->user()),
        ], 'Notification preferences retrieved.');
    }

    /**
     * Replace the preference matrix of the authenticated user.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['boolean'],
        ]);

        $this->service->updatePreferences($request->user(), $payload['matrix']);

        return $this->success([
            'categories' => NotificationService::CATEGORIES,
            'channels' => NotificationService::CHANNELS,
            'matrix' => $this->service->preferences($request->user()),
        ], 'Notification preferences updated.');
    }

    /**
     * Shape a notification row into a safe client payload.
     *
     * @return array<string, mixed>
     */
    protected function shape(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : json_decode((string) $notification->data, true) ?? [];

        return [
            'id' => $notification->getKey(),
            'type' => $data['type'] ?? 'system',
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? '',
            'category' => $data['category'] ?? $data['type'] ?? 'system',
            'data' => $data,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}