<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Notify;

class DonationController extends Controller
{
    // ✅ عرض التبرعات
    public function index(Request $request)
    {
        $query = Donation::with(['case', 'donor']);

        if ($request->user()?->user_type !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        if ($caseId = $request->query('case_id')) {
            $query->where('case_id', $caseId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    // ✅ عرض تبرع محدد
    public function show(Request $request, $id)
    {
        $donation = Donation::with(['case', 'donor'])->findOrFail($id);

        if ($request->user()?->user_type !== 'admin' && $donation->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($donation);
    }

    // ✅ إنشاء تبرع جديد
    public function store(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string', // cash, shamm, stripe, paypal...
            'note' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $caseId = $request->case_id;
        $amount = (float) $request->amount;
        $method = $request->method;

        $donation = null;

        DB::transaction(function () use ($user, $caseId, $amount, $method, $request, &$donation) {
            $case = CaseModel::lockForUpdate()->findOrFail($caseId);

            $status = 'pending'; // الكل pending حتى يتم التأكيد

            $donation = Donation::create([
                'case_id' => $case->id,
                'user_id' => $user?->id,
                'amount' => $amount,
                'method' => $method,
                'note' => $request->input('note'),
                'status' => $status,
            ]);
        });

        // ✅ ShamCash → رجع صورة الكود
        if ($method === 'shamm') {
            return response()->json([
                'message' => 'تبرعك مسجل بانتظار التحويل عبر ShamCash',
                'donation' => $donation,
                'instructions' => [
                    'qr_image' => asset('storage/shamcash_qr.png'),
                    'account_number' => '0933XXXXXXX',
                    'note' => 'رجاءً قم برفع صورة إيصال التحويل ليتم تأكيد التبرع',
                ]
            ], 201);
        }

        // ✅ Cash → تعليمات استلام يدوي
        if ($method === 'cash') {
            return response()->json([
                'message' => 'تبرعك مسجل بانتظار التسليم النقدي',
                'donation' => $donation,
                'instructions' => [
                    'note' => 'يرجى تسليم المبلغ للإدارة ليتم تأكيده',
                ]
            ], 201);
        }

        // ✅ Online (Stripe, PayPal...) → mock
        return response()->json([
            'message' => 'Donation created, awaiting payment confirmation',
            'donation' => $donation,
            'payment' => [
                'mock' => true,
                'note' => 'simulate payment confirmation by calling /api/donations/{id}/confirm',
            ]
        ], 201);
    }

    // ✅ رفع إيصال التحويل (لـ ShamCash أو Cash)
    public function uploadReceipt(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'receipt' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('receipt')->store("donations/{$donation->id}", 'public');

        $donation->update([
            'receipt_path' => $path,
        ]);

        return response()->json([
            'message' => 'تم رفع صورة الإيصال، بانتظار تأكيد الإدارة',
            'donation' => $donation,
        ]);
    }

    // ✅ تأكيد التبرع (من الأدمن أو من بوابة دفع)
    public function confirm(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->status === 'completed') {
            return response()->json(['message' => 'Already completed'], 200);
        }

        DB::transaction(function () use ($donation) {
            $donation->status = 'completed';
            $donation->save();

            $case = CaseModel::lockForUpdate()->findOrFail($donation->case_id);
            $case->collected_amount = $case->collected_amount + $donation->amount;
            if ($case->collected_amount >= $case->goal_amount) {
                $case->status = 'completed';
            }
            $case->save();
        });

        $case = CaseModel::find($donation->case_id);
        if ($case && $case->organization?->email) {
            Mail::raw("تم تأكيد تبرع لموضوع: {$case->title}\nالمبلغ: {$donation->amount}", function ($m) use ($case) {
                $m->to($case->organization->email)->subject('تأكيد تبرع');
            });
        }


        // إشعار للجمعية
        if ($donation->case->organization?->user_id) {
            Notify::send(
                $donation->case->organization->user_id,
                'تبرع جديد وصل 🎁',
                "تم تأكيد تبرع بمبلغ {$donation->amount} للحالة ({$donation->case->title}).",
                'donation'
            );
        }

        return response()->json(['message' => 'Donation confirmed']);
    }

}
