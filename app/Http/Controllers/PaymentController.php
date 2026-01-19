<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth:sanctum')->only(['donate','walletBalance']);
        // admin middleware for confirm
        $this->middleware('auth:sanctum')->only(['confirmPayment']);
        $this->paymentService = $paymentService;
    }

    /**
     * Donate endpoint
     * POST /api/payments/donate
     * body: { amount, currency (optional), method (wallet|shamcash|cash), organization_id?, project_id?, case_id? }
     */
    public function donate(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'method' => 'required|in:wallet,shamcash,cash,card,other',
            'organization_id' => 'nullable|exists:organizations,id',
            'project_id' => 'nullable|exists:projects,id',
            'case_id' => 'nullable|exists:cases,id',
            'meta' => 'nullable|array'
        ]);

        // create initial payment record (status depends on method)
        $payment = Payment::create([
            'user_id' => $user->id,
            'organization_id' => $data['organization_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'case_id' => $data['case_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'TRY',
            'method' => $data['method'],
            'status' => ($data['method'] === 'wallet') ? 'pending' : 'pending',
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->json([
            'message' => 'Donation recorded as pending. It will be marked completed after admin confirmation.',
            'payment_id' => $payment->id,
            'status' => 'pending'
        ], 201);
    }

    /**
     * Admin confirms pending payment (e.g. after verifying ShamCash/cash)
     * POST /api/payments/{id}/confirm
     */
    public function confirmPayment(Request $request, $id)
    {
        $admin = Auth::user();
        if ($admin->user_type !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        $payment = Payment::findOrFail($id);
        if ($payment->status !== 'pending') {
            return response()->json(['message' => 'Payment not pending'], 422);
        }

        $payment->update([
            'amount' => $data['amount'],
            'platform_fee' => 0,
            'points_share' => 0,
            'system_share' => 0,
            'net_amount' => $data['amount'],
            'status' => 'completed',
        ]);

        return response()->json(['message' => 'Payment confirmed by admin', 'payment' => $payment->fresh()]);
    }

    /**
     * Simple endpoint to see wallet balance for current user
     * GET /api/wallet/balance
     */
    public function walletBalance(Request $request)
    {
        $user = Auth::user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['name' => $user->name . ' wallet']);
        return response()->json(['balance' => (float) $wallet->balance]);
    }

    /**
     * List payments (donations) with optional filters.
     * GET /api/payments?project_id=&case_id=&status=&per_page=
     * - Admin sees all; regular users see their own payments only.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Payment::with(['user:id,name']);

        if (!$user || $user->user_type !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($projectId = $request->query('project_id')) {
            $query->where('project_id', $projectId);
        }
        if ($caseId = $request->query('case_id')) {
            $query->where('case_id', $caseId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', 20);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }
}
