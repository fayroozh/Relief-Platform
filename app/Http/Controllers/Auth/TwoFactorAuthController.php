<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

class TwoFactorAuthController extends Controller
{
    /**
     * Enable two-factor authentication for the user.
     */
    public function store(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user());

        return response()->json([
            'message' => 'Two-factor authentication has been enabled. Scan the QR code and confirm.',
            'qr_code_svg' => $request->user()->twoFactorQrCodeSvg(),
            'recovery_codes' => $request->user()->recoveryCodes(),
        ]);
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $confirm($request->user(), $request->input('code'));

        return response()->json([
            'message' => 'Two-factor authentication has been confirmed and enabled successfully.',
        ]);
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function destroy(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable($request->user());

        return response()->json([
            'message' => 'Two-factor authentication has been disabled.',
        ]);
    }

    /**
     * Get the user's two-factor recovery codes.
     */
    public function show(Request $request)
    {
        if (!$request->user()->two_factor_secret) {
            return response()->json(['message' => '2FA is not enabled for this user.'], 404);
        }

        return response()->json([
            'recovery_codes' => $request->user()->recoveryCodes(),
        ]);
    }
}