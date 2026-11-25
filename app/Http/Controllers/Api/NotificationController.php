<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (AppNotification $item) => $this->formatNotification($item));

        return $this->successResponse($notifications, 'Daftar notifikasi berhasil diambil');
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->latest()
            ->take(20)
            ->get()
            ->map(fn (AppNotification $item) => $this->formatNotification($item));

        return $this->successResponse($notifications, 'Daftar notifikasi belum dibaca berhasil diambil');
    }

    public function markAsRead(Request $request, string|int $id): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)->find($id);
        if (!$notification) {
            return $this->errorResponse('Notifikasi tidak ditemukan', 404);
        }

        $notification->update(['is_read' => true]);

        return $this->successResponse(
            $this->formatNotification($notification),
            'Notifikasi ditandai sudah dibaca'
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->successResponse(null, 'Semua notifikasi ditandai sudah dibaca');
    }

    private function formatNotification(AppNotification $item): array
    {
        return [
            'id' => $item->id,
            'userId' => $item->user_id,
            'title' => $item->title,
            'message' => $item->message,
            'type' => $item->type,
            'referenceId' => $item->reference_id,
            'isRead' => $item->is_read,
            'createdAt' => $item->created_at?->toISOString(),
        ];
    }
}

