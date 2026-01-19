<?php

namespace App\Http\Controllers;

use App\Models\PendingDonation;
use App\Models\Donation;
use App\Models\CaseModel;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Helpers\Notify;
use App\Models\User;

class PendingDonationController extends Controller
{
    // ================================
    // 1) إنشاء تبرع معلّق (User)
    // ================================
    public function store(Request $request)
    {
        $request->validate([
            'case_id' => 'nullable|exists:cases,id',
            'project_id' => 'nullable|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string|max:50',
            'payer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'note' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        // 🔥 منع التبرع لنفس الكيان أكثر من 3 مرات (حالة أو مشروع)
        $countQuery = PendingDonation::where('user_id', $user->id);
        if ($request->filled('case_id')) {
            $countQuery->where('case_id', $request->case_id);
        }
        if ($request->filled('project_id')) {
            $countQuery->where('project_id', $request->project_id);
        }
        $count = $countQuery->count();

        if ($count >= 3) {
            return response()->json([
                'message' => 'لا يمكنك التبرع لنفس الحالة أكثر من 3 مرات'
            ], 403);
        }

        // إنشاء التبرع المعلّق
        $pending = PendingDonation::create([
            'user_id' => $user->id,
            'case_id' => $request->case_id,
            'project_id' => $request->project_id,
            'amount' => $request->amount,
            'payer_name' => $request->payer_name,
            'phone' => $request->phone,
            'note' => $request->note,
            'status' => 'pending'
        ]);

        $admins = User::where('user_type', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            Notify::send(
                $adminId,
                'تبرع معلّق جديد',
                "طلب تبرع جديد بقيمة {$pending->amount}",
                'donations'
            );
        }
        Notify::send(
            $user->id,
            'تم استلام طلب التبرع',
            "تم استلام طلبك بقيمة {$pending->amount} وهو قيد المراجعة",
            'donations'
        );

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
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending'); // pending | approved | rejected | all

        $query = PendingDonation::with(['case', 'user', 'project'])
            ->orderBy('created_at', 'asc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->get());
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

        $donation = null;
        $case = null;
        $project = null;

        if ($pending->case_id) {
            $case = CaseModel::findOrFail($pending->case_id);
            $donation = Donation::create([
                'user_id' => $pending->user_id,
                'case_id' => $pending->case_id,
                'amount' => $request->confirmed_amount,
                'method' => 'shamcash',
                'note' => $pending->note,
                'status' => 'completed'
            ]);
            $case->collected_amount += $request->confirmed_amount;
            if ($case->collected_amount >= $case->goal_amount) {
                $case->status = 'completed';
            }
            $case->save();
        } elseif ($pending->project_id) {
            $project = Project::findOrFail($pending->project_id);
            
            // إنشاء سجل تبرع للمشروع أيضاً
            $donation = Donation::create([
                'user_id' => $pending->user_id,
                'project_id' => $pending->project_id,
                'amount' => $request->confirmed_amount,
                'method' => 'shamcash',
                'note' => $pending->note,
                'status' => 'completed'
            ]);

            $project->raised_amount = ($project->raised_amount ?? 0) + $request->confirmed_amount;
            if ($project->raised_amount >= $project->goal_amount) {
                $project->status = 'completed';
            }
            $project->save();
        }

        // تحديث التبرع المعلق
        $pending->status = 'approved';
        $pending->save();

        Notify::send(
            $pending->user_id,
            'تم تأكيد التبرع',
            "تم تأكيد تبرعك بقيمة {$request->confirmed_amount}$",
            'donations'
        );
        if ($case && $case->organization) {
            Notify::send(
                $case->organization->user_id,
                'تبرع جديد مؤكد',
                "تم تأكيد تبرع بقيمة {$request->confirmed_amount}$ للحالة {$case->title}",
                'donations'
            );
        }
        if ($project) {
            Notify::send(
                $project->created_by_id,
                'تبرع جديد مؤكد',
                "تم تأكيد تبرع بقيمة {$request->confirmed_amount}$ للمشروع {$project->title}",
                'donations'
            );
        }

        return response()->json([
            'message' => 'تم تأكيد التبرع وإضافته بنجاح',
            'donation' => $donation,
            'project' => $project
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

        $targetText = $pending->case_id ? "للحالة {$pending->case_id}" : ($pending->project_id ? "للمشروع {$pending->project_id}" : '');
        Notify::send(
            $pending->user_id,
            'تم رفض طلب التبرع',
            "تم رفض طلب تبرعك {$targetText}",
            'donations'
        );

        return response()->json(['message' => 'تم رفض التبرع']);
    }
}
