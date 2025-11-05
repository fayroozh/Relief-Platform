<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ✅ عرض إشعارات المستخدم
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    // ✅ تحديد إشعار كمقروء
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    // ✅ حذف إشعار
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    // ✅ إرسال إشعار عام (للأدمن فقط)
    public function broadcast(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'target_group' => 'required|in:all_users,organizations,donors',
        ]);

        $user = $request->user();
        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::query();

        if ($request->target_group === 'organizations') {
            $query->where('user_type', 'organization');
        } elseif ($request->target_group === 'donors') {
            $query->where('user_type', 'user');
        }

        $recipients = $query->pluck('id');

        foreach ($recipients as $receiverId) {
            Notification::create([
                'user_id' => $receiverId,
                'title' => $request->title,
                'message' => $request->message,
                'type' => 'admin',
            ]);
        }

        return response()->json(['message' => 'Notification broadcasted successfully']);
    }
}
