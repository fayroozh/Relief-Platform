<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;


class MessageController extends Controller
{
    // ✅ إرسال رسالة للإدارة
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $user = $request->user();

        // إرسال الرسالة لجميع الأدمنز
        $admins = User::where('user_type', 'admin')->pluck('id');

        foreach ($admins as $adminId) {
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $adminId,
                'subject' => $request->subject,
                'content' => $request->content,
            ]);
            $admin = User::find($adminId);
            $admin->notify(new GeneralNotification(
                '📩 رسالة جديدة من ' . $user->name,
                $request->subject ? "{$request->subject}: {$request->content}" : $request->content,
                null
            ));

        }
        $receiver = User::find($receiverId);
        $receiver->notify(new GeneralNotification(
            '📢 إشعار جديد من الإدارة',
            $request->subject ? "{$request->subject}: {$request->content}" : $request->content,
            null
        ));



        return response()->json(['message' => 'Message sent successfully']);
    }

    // ✅ عرض الرسائل (للمستخدم أو الأدمن)
    public function index(Request $request)
    {
        $user = $request->user();

        $messages = \App\Models\Message::where(function ($q) use ($user) {
            $q->where('receiver_id', $user->id)
                ->orWhere(function ($sub) use ($user) {
                    $sub->whereIn('target_group', ['all_users'])
                        ->orWhere(function ($inner) use ($user) {
                            $inner->where('target_group', $user->user_type === 'organization' ? 'organizations' : 'donors');
                        });
                });
        })
            ->with(['sender:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }


    // ✅ تحديد رسالة كمقروءة
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update(['is_read' => true]);

        return response()->json(['message' => 'Message marked as read']);
    }

    // ✅ حذف رسالة
    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
    // ✅ عرض الرسائل الواردة إلى الإدارة فقط (Inbox للأدمن)
    public function adminInbox(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = \App\Models\Message::where('receiver_id', $user->id)
            ->with(['sender:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }
    // ✅ إرسال رسالة عامة من الأدمن
    public function broadcast(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'subject' => 'nullable|string|max:255',
            'target_group' => 'required|in:all_users,organizations,donors',
        ]);

        $user = $request->user();
        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // اختيار الفئة المستهدفة
        $query = \App\Models\User::query();

        if ($request->target_group === 'organizations') {
            $query->where('user_type', 'organization');
        } elseif ($request->target_group === 'donors') {
            $query->where('user_type', 'user');
        }

        $recipients = $query->pluck('id');

        foreach ($recipients as $receiverId) {
            \App\Models\Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'subject' => $request->subject,
                'content' => $request->content,
                'target_group' => $request->target_group,
            ]);
        }

        return response()->json(['message' => 'Message broadcasted successfully']);
    }


}
