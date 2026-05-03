<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;

class StudentNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'message' => 'Notifications berhasil dimuat.',
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                    'unreadCount' => 0,
                ],
            ]);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $notifications = $user
            ->notifications()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Notifications berhasil dimuat.',
            'data' => [
                'notifications' => $notifications->through(
                    fn (DatabaseNotification $notification) => $this->formatNotification($notification)
                ),
                'unread_count' => $user->unreadNotifications()->count(),
                'unreadCount' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'message' => 'Notification table belum tersedia.',
            ], 422);
        }

        $notification = $user
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Notification tidak ditemukan.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification berhasil ditandai sudah dibaca.',
            'data' => [
                'notification' => $this->formatNotification($notification->fresh()),
                'unread_count' => $user->unreadNotifications()->count(),
                'unreadCount' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'message' => 'Notification table belum tersedia.',
            ], 422);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Semua notification berhasil ditandai sudah dibaca.',
            'data' => [
                'unread_count' => 0,
                'unreadCount' => 0,
            ],
        ]);
    }

    private function formatNotification(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? $this->normalizeType($notification->type),

            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? $data['body'] ?? '-',

            'to' => $data['to'] ?? $data['url'] ?? '/notifications',
            'url' => $data['url'] ?? $data['to'] ?? '/notifications',

            'is_read' => filled($notification->read_at),
            'isRead' => filled($notification->read_at),

            'read_at' => optional($notification->read_at)->toISOString(),
            'readAt' => optional($notification->read_at)->toISOString(),

            'created_at' => optional($notification->created_at)->toISOString(),
            'createdAt' => optional($notification->created_at)->toISOString(),

            'time' => optional($notification->created_at)->diffForHumans(),

            'data' => $data,
        ];
    }

    private function normalizeType(?string $notificationClass): string
    {
        if (! $notificationClass) {
            return 'general';
        }

        $class = class_basename($notificationClass);

        return str($class)
            ->replace('Notification', '')
            ->kebab()
            ->toString();
    }
}