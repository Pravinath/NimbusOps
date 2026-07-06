<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(25),
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => $record->fresh(),
        ]);
    }
}
