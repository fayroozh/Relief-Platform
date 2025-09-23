<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;

class TopUpController extends Controller
{
    public function topUp(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['name' => $user->name . ' wallet']);
        $wallet->credit($request->amount, null, 'topup', ['method' => 'cash']);

        return response()->json([
            'message' => 'Wallet topped up successfully',
            'balance' => $wallet->balance
        ]);
    }
}
