<?php
namespace App\Services;

use App\Models\PointTransaction;
use App\Models\User;

class PointService
{
    public function pointsForAmount(float $amountTRY): int
    {
        $per = config('points.points_per_try', 10);
        return (int) round($amountTRY * $per);
    }

    public function creditPoints(User $user, int $points, $source = 'donation', $paymentId = null, $meta = [])
    {
        $user->increment('points_balance', $points);
        return PointTransaction::create([
            'user_id' => $user->id,
            'points' => $points,
            'type' => 'earned',
            'source' => $source,
            'payment_id' => $paymentId,
            'meta' => $meta
        ]);
    }

    public function spendPoints(User $user, int $points, $source = 'spend', $meta = [])
    {
        if ($user->points_balance < $points) {
            throw new \Exception('Not enough points');
        }
        $user->decrement('points_balance', $points);
        return PointTransaction::create([
            'user_id' => $user->id,
            'points' => -$points,
            'type' => 'spent',
            'source' => $source,
            'meta' => $meta
        ]);
    }
    public static function addPoints(User $user, int $points, string $source, array $meta = [])
    {
        DB::transaction(function () use ($user, $points, $source, $meta) {
            // سجّل الحركة
            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'source' => $source,
                'meta' => $meta,
            ]);

            // زيد رصيد المستخدم
            $user->increment('points', $points);
        });
    }
}
