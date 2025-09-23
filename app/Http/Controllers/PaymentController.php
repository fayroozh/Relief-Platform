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

        // If wallet -> perform immediate transfer from donor wallet
        if ($data['method'] === 'wallet') {
            // find donor wallet
            $donorWallet = Wallet::firstOrCreate(['user_id' => $user->id], ['name' => $user->name . ' wallet', 'balance' => 0]);
            DB::beginTransaction();
            try {
                // check balance
                if ($donorWallet->balance < $data['amount']) {
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient wallet balance'], 422);
                }

                // debit donor
                $donorWallet->debit($data['amount'], $payment->id, 'donation_debit');

                // mark payment pending -> then execute
                $payment->update(['status' => 'pending']);

                // execute allocation (credits destination/system/points + award points)
                $this->paymentService->executePayment($payment);

                DB::commit();

                return response()->json([
                    'message' => 'Donation completed successfully',
                    'payment' => $payment->fresh()
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Error processing wallet payment', 'error' => $e->getMessage()], 500);
            }
        }

        // For cash/shamcash/card -> leave pending, admin confirmation required (or webhook)
        return response()->json([
            'message' => 'Donation recorded as pending. It will be marked completed after confirmation.',
            'payment_id' => $payment->id
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

        $payment = Payment::findOrFail($id);
        if ($payment->status !== 'pending') {
            return response()->json(['message' => 'Payment not pending'], 422);
        }

        try {
            $this->paymentService->executePayment($payment);
            return response()->json(['message' => 'Payment executed/confirmed', 'payment' => $payment->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error executing payment', 'error' => $e->getMessage()], 500);
        }
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
}
