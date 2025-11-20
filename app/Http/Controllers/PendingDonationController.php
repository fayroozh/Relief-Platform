<?php

namespace App\Http\Controllers;

use App\Models\PendingDonation;
use App\Models\Donation;
use App\Models\CaseModel;
use Illuminate\Http\Request;

class PendingDonationController extends Controller
{
    // ================================
    // 1) إنشاء تبرع معلّق (User)
    // ================================
    public function store(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        // 🔥 منع التبرع لنفس الحالة أكثر من 3 مرات
        $count = PendingDonation::where('user_id', $user->id)
            ->where('case_id', $request->case_id)
            ->count();

        if ($count >= 3) {
            return response()->json([
                'message' => 'لا يمكنك التبرع لنفس الحالة أكثر من 3 مرات'
            ], 403);
        }

        // إنشاء التبرع المعلّق
        $pending = PendingDonation::create([
            'user_id' => $user->id,
            'case_id' => $request->case_id,
            'amount' => $request->amount,
            'note' => $request->note,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب التبرع بنجاح، بانتظار تأكيد الإدارة',
            'pending_donation' => $pending
        ], 201);
    }


    // ================================
    // 2) عرض تبرعاتي المعلّقة
    // ================================
    public function myPending(Request $request)
    {
        $user = $request->user();

        $data = PendingDonation::with('case')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($data);
    }


    // ================================
    // 3) قائمة الانتظار (Admin)
    // ================================
    public function adminIndex()
    {
        $pending = PendingDonation::with(['case', 'user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pending);
    }


    // ================================
    // 4) قبول التبرع (Admin)
    // ================================
    public function approve(Request $request, $id)
    {
        $request->validate([
            'confirmed_amount' => 'required|numeric|min:1'
        ]);

        $pending = PendingDonation::findOrFail($id);

        if ($pending->status !== 'pending') {
            return response()->json(['message' => 'تمت معالجة هذا التبرع مسبقاً'], 400);
        }

        $case = CaseModel::findOrFail($pending->case_id);

        // إنشاء تبرع رسمي
        $donation = Donation::create([
            'user_id' => $pending->user_id,
            'case_id' => $pending->case_id,
            'amount' => $request->confirmed_amount,
            'method' => 'sham-cash',
            'note' => $pending->note,
            'status' => 'completed'
        ]);

        // تحديث الحالة
        $case->collected_amount += $request->confirmed_amount;
        if ($case->collected_amount >= $case->goal_amount) {
            $case->status = 'completed';
        }
        $case->save();

        // تحديث التبرع المعلق
        $pending->status = 'approved';
        $pending->admin_confirmed_amount = $request->confirmed_amount;
        $pending->save();

        return response()->json([
            'message' => 'تم تأكيد التبرع وإضافته بنجاح',
            'donation' => $donation
        ]);
    }


    // ================================
    // 5) رفض التبرع (Admin)
    // ================================
    public function reject(Request $request, $id)
    {
        $pending = PendingDonation::findOrFail($id);

        if ($pending->status !== 'pending') {
            return response()->json(['message' => 'تمت معالجة هذا التبرع مسبقاً'], 400);
        }

        $pending->status = 'rejected';
        $pending->save();

        return response()->json(['message' => 'تم رفض التبرع']);
    }
}
