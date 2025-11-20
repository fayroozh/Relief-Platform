<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthenticatedSessionController extends Controller
{
    // ✅ تسجيل الدخول عبر API
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            return response()->json([
                'message' => 'Two-factor authentication is required.',
                'two_factor' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // ✅ تسجيل خروج
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete(); // حذف التوكن الحالي

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function twoFactorChallenge(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect or 2FA is not enabled.'],
            ]);
        }

        $provider = app(TwoFactorAuthenticationProvider::class);
        if (!$provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor code is invalid.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
