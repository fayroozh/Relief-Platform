<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;




class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
            'phone' => 'nullable|string|unique:users,phone',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? null,
            'password' => Hash::make($request->password),
        ]);

        // ✅ توليد OTP
        $otp = rand(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // ✅ إرسال الكود على الإيميل
        Mail::raw("Your verification code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your Verification Code');
        });


        return response()->json([
            'message' => 'User registered. OTP sent to your email.',
            'user_id' => $user->id
        ]);
    }



    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'otp_code' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Invalid code'], 401);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Code expired'], 401);
        }

        // مسح الكود بعد التحقق
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        // إنشاء التوكن بعد نجاح التحقق
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Verification successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    
}
