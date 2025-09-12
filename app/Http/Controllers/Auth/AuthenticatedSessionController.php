<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    // ✅ تسجيل الدخول عبر الإيميل + باسورد
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // إنشاء توكن
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // ✅ تسجيل الدخول عبر رقم الهاتف (بدون OTP)
    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
        ]);

        $user = \App\Models\User::firstOrCreate(
            ['phone' => $request->phone],
            ['name' => $request->name, 'email' => null, 'password' => bcrypt(str()->random(16))]
        );

        if ($user->name !== $request->name) {
            $user->update(['name' => $request->name]);
        }

        // إنشاء توكن
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login with phone successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // ✅ تسجيل خروج
    public function destroy(Request $request)
    {
        Auth::logout();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
