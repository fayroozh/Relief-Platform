<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    // 1) طلب إعادة تعيين
    public function requestReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = rand(100000, 999999);
        $token = Str::uuid();

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'reset_token' => $token,
            'reset_expires_at' => now()->addMinutes(15),
        ]);

        // إرسال الكود عالإيميل
        Mail::raw("Your reset code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Reset Code');
        });

        return response()->json([
            'message' => 'Reset code sent to your email',
            'reset_token' => $token
        ]);
    }

    // 2) التحقق من الكود
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'reset_token' => 'required|string',
            'otp_code' => 'required|digits:6',
        ]);

        $user = User::where('reset_token', $request->reset_token)->first();

        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Invalid code'], 401);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Code expired'], 401);
        }

        return response()->json([
            'message' => 'Code verified. You can now reset your password.',
            'reset_token' => $user->reset_token
        ]);
    }

    // 3) تغيير كلمة المرور
    public function resetPassword(Request $request)
    {
        $request->validate([
            'reset_token' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::where('reset_token', $request->reset_token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (now()->greaterThan($user->reset_expires_at)) {
            return response()->json(['message' => 'Reset session expired'], 401);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp_code' => null,
            'otp_expires_at' => null,
            'reset_token' => null,
            'reset_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successful']);
    }
}
