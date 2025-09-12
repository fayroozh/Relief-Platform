<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use Illuminate\Support\Str;


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

        // ✅ إذا عنده رقم، إرسال عبر WhatsApp (Twilio)
        if ($user->phone) {
            $twilio = new Client(
                env('TWILIO_SID'),
                env('TWILIO_AUTH_TOKEN')
            );

            $twilio->messages->create("whatsapp:" . $user->phone, [
                "from" => "whatsapp:" . env('TWILIO_WHATSAPP'),
                "body" => "Your verification code is: $otp"
            ]);
        }

        return response()->json([
            'message' => 'User registered. OTP sent to your email/phone.',
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

    public function registerPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|unique:users,phone',
        ]);

        // إنشاء مستخدم مبدئي برقم فقط
        $user = User::create([
            'name' => 'User_' . rand(1000, 9999),
            'email' => 'temp_' . uniqid() . '@example.com',
            'phone' => $request->phone,
            'password' => Hash::make(Str::random(8)),
        ]);

        // توليد OTP
        $otp = rand(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // إرسال OTP عبر SMS
        $twilio = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );

        $twilio->messages->create($user->phone, [
            "from" => env('TWILIO_SMS_FROM'), // رقم Twilio صالح لإرسال SMS
            "body" => "Your verification code is: $otp"
        ]);

        return response()->json([
            'message' => 'OTP sent to your phone',
            'user_id' => $user->id
        ]);
    }

    public function verifyPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|digits:6',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Invalid code'], 401);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Code expired'], 401);
        }

        // ✅ التحقق ناجح → تحديث حالة التحقق
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'phone_verified_at' => now(),
        ]);

        // إنشاء توكن
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Phone verification successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

}
