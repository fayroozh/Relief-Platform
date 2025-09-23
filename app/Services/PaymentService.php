<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\PointService;
use Carbon\Carbon;

class PaymentService
{
    protected $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    /**
     * Execute allocation when payment is completed
     * - splits fees
     * - credits wallets (destination, system, points pool)
     * - awards points to user
     */
    public function executePayment(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            // recalc safety
            $amount = (float) $payment->amount;
            $platformFeePercent = config('points.platform_fee_percent', 0.03);
            $pointsPercent = config('points.platform_points_percent', 0.01);
            $systemPercent = config('points.platform_system_percent', 0.02);

            $platform_fee = round($amount * $platformFeePercent, 2);
            $points_share = round($amount * $pointsPercent, 2);
            $system_share = round($amount * $systemPercent, 2);
            $net_amount = round($amount - $platform_fee, 2);

            // update payment
            $payment->update([
                'platform_fee' => $platform_fee,
                'points_share' => $points_share,
                'system_share' => $system_share,
                'net_amount' => $net_amount,
                'status' => 'completed',
            ]);

            // get or create wallets
            // destination wallet: project -> organization -> case owner
            $destinationWallet = null;
            if ($payment->organization_id) {
                $destinationWallet = Wallet::firstOrCreate(
                    ['organization_id' => $payment->organization_id],
                    ['name' => 'Organization wallet']
                );
            } elseif ($payment->project_id) {
                $destinationWallet = Wallet::firstOrCreate(
                    ['project_id' => $payment->project_id],
                    ['name' => 'Project wallet']
                );
            } else {
                // if neither, money goes to platform project wallet (platform keeps it)
                $destinationWallet = Wallet::where('is_system', true)->where('name', 'Platform main')->first();
                if (!$destinationWallet) {
                    $destinationWallet = Wallet::create([
                        'name' => 'Platform main',
                        'is_system' => true,
                        'balance' => 0
                    ]);
                }
            }

            // system wallet (operational)
            $systemWallet = Wallet::where('is_system', true)->where('name', 'Platform system')->first();
            if (!$systemWallet) {
                $systemWallet = Wallet::create([
                    'name' => 'Platform system',
                    'is_system' => true,
                    'balance' => 0
                ]);
            }

            // points pool wallet (system)
            $pointsPool = Wallet::where('is_system', true)->where('name', 'Points pool')->first();
            if (!$pointsPool) {
                $pointsPool = Wallet::create([
                    'name' => 'Points pool',
                    'is_system' => true,
                    'balance' => 0
                ]);
            }

            // credit destination with net_amount
            $destinationWallet->credit($net_amount, $payment->id, 'payment_destination', ['note' => 'Net amount to destination']);

            // credit system wallet (system_share)
            if ($system_share > 0) {
                $systemWallet->credit($system_share, $payment->id, 'platform_fee_system');
            }

            // credit points pool
            if ($points_share > 0) {
                $pointsPool->credit($points_share, $payment->id, 'platform_fee_points');
            }

            // award points to user (based on amount)
            if ($payment->user_id) {
                $user = User::find($payment->user_id);
                if ($user) {
                    $points = $this->pointService->pointsForAmount($amount);
                    $this->pointService->creditPoints($user, $points, 'donation', $payment->id, [
                        'amount' => $amount,
                        'currency' => $payment->currency
                    ]);
                }
            }

            // update any case collected_amount if present
            if ($payment->case_id) {
                $case = \App\Models\CaseModel::find($payment->case_id);
                if ($case) {
                    $case->increment('collected_amount', $net_amount);
                }
            }

            return $payment->fresh();
        });
    }
}
