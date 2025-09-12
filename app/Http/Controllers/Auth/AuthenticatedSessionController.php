<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // ✅ توليد كود 6 أرقام عشوائي
        $otp = rand(100000, 999999);

        // ✅ حفظ الكود بقاعدة البيانات مع وقت انتهاء (10 دقائق)
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // ✅ إرسال الكود على الإيميل
        Mail::raw("Your verification code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your Verification Code');
        });

        // ✅ رجع استجابة مع user_id
        return response()->json([
            'message' => 'OTP sent to your email. Please verify.',
            'user_id' => $user->id,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

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

        // إنشاء التوكن
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Verification successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }


    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
        ]);

        // إذا المستخدم موجود مسبقاً نحدث اسمه
        $user = \App\Models\User::firstOrCreate(
            ['phone' => $request->phone],
            ['name' => $request->name, 'email' => null, 'password' => bcrypt(str()->random(16))]
        );

        if ($user->name !== $request->name) {
            $user->update(['name' => $request->name]);
        }

        $otp = rand(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(2)
        ]);

        // إرسال SMS عبر Twilio
        $twilio = new \Twilio\Rest\Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );

        
        $twilio->messages->create("whatsapp:" . $user->phone, [
            "from" => "whatsapp:" . env('TWILIO_WHATSAPP'),
            "body" => "Your WhatsApp verification code is: $otp"
        ]);


        return response()->json(['message' => 'OTP sent to your phone.']);
    }

    public function verifyPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|digits:6',
        ]);

        $user = \App\Models\User::where('phone', $request->phone)->first();

        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Invalid code'], 401);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Code expired'], 401);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Phone verification successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }



    public function destroy(Request $request)
    {
        Auth::logout();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
