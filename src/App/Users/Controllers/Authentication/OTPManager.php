<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OTPManager extends Controller
{

    public function resendOtpCode(Request $request)
    {
        // Validate that user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $request->user();
        
        // Check if user already has a valid unexpired OTP code
        if ($user->otp_code && !$user->otpCodeIsExpired()) {
            return response()->json(['message' => 'Valid OTP code already exists'], 429);
        }

        $user->sendNewOtpCode();

        return response()->noContent();
    }

    /**
     * Send OTP authentication code to user
     * @param $email
     * @param bool $checkExpiration
     * @return bool
     */
    private function sendOtpAuthCode($email, $checkExpiration = true)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }
        
        if (!$checkExpiration || $user->otpCodeIsExpired()) {
            $user->sendNewOtpCode();
            return true;
        }
        
        return false;
    }

    public function validateOtpCode(Request $request)
    {
        // Validate input
        $request->validate([
            'otp_code' => 'required|string|size:6|regex:/^[0-9]{6}$/'
        ]);

        $user = auth()->user();
        
        // Check if user is authenticated
        if (!$user) {
            return new JsonResponse([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Validate otp code - can be invalid or expired - same error to pass back.
        if (!$user->verifyOtpCode((string) $request->input('otp_code'))) {
            return new JsonResponse([
                'otp_code' => __('Security Code is Invalid or Expired!'),
            ], 422);
        }

        $user->resetOtpCode();

        return response()
            ->json([], 200)
            ->cookie('access_token', $request->bearerToken(), 43200);
    }

}