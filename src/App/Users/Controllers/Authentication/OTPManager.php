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
        if (auth()->check()) {
            $request->user()->sendNewOtpCode();

            return response()->noContent();
        }
    }

    /**
     * @param $email
     * @param bool $checkExpiration
     * @return bool
     */
    private function sendOtpAuthCode($email, $checkExpiration = true)
    {
        $user = User::query()->where('email', $email)->first();
        if ($user && (!$checkExpiration || $user->otpCodeIsExpired())) {
            $user->sendNewOtpCode();
            return true;
        }
        return false;
    }

    public function validateOtpCode(Request $request)
    {
        // Validate otp code - can be invalid or expired - same error to pass back.
        if (
            ! $request->input('otp_code') ||
            ! \auth()->user() ||
            ! \auth()->user()->verifyOtpCode((string) $request->input('otp_code'))
        ) {
            return new JsonResponse([
                'otp_code'=>__('Security Code is Invalid or Expired!'),
            ], 424);
        }

        \auth()->user()->resetOtpCode();

        return response()
            ->json([], 200)
            ->cookie('access_token', $request->bearerToken(), 43200);
    }

}
