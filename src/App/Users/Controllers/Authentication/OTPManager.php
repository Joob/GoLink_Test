<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OTPManager extends Controller
{
    public function sendOtpCode(Request $request): JsonResponse
    {
        // Validate that user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $request->user();
        $result = $user->sendNewOtpCodeWithRateLimit();

        if (!$result['success']) {
            $statusCode = match($result['error']) {
                'valid_otp_exists' => 409, // Conflict
                'cooldown_active' => 429,  // Too Many Requests
                'rate_limited' => 429,     // Too Many Requests
                default => 400
            };

            $response = ['message' => $result['message']];
            
            // Add timing information for frontend
            if (isset($result['remaining_cooldown'])) {
                $response['remaining_cooldown'] = $result['remaining_cooldown'];
            }
            if (isset($result['remaining_wait'])) {
                $response['remaining_wait'] = $result['remaining_wait'];
            }
            if (isset($result['remaining_validity'])) {
                $response['remaining_validity'] = $result['remaining_validity'];
            }

            return response()->json($response, $statusCode);
        }

        return response()->json(['message' => $result['message']], 200);
    }

    public function resendOtpCode(Request $request): JsonResponse
    {
        return $this->sendOtpCode($request);
    }

    public function validateOtpCode(Request $request): JsonResponse
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

        // Validate otp code
        if (!$user->verifyOtpCode((string) $request->input('otp_code'))) {
            return new JsonResponse([
                'otp_code' => __('Security Code is Invalid or Expired!'),
            ], 422);
        }

        // Reset OTP code and attempts on successful validation
        $user->resetOtpCode();
        $user->fullResetOtpAttempts();

        return response()
            ->json([], 200)
            ->cookie('access_token', $request->bearerToken(), 43200);
    }

    /**
     * Get OTP status for frontend
     */
    public function getOtpStatus(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $request->user();

        return response()->json([
            'attempts_used' => $user->otp_attempts_used,
            'is_in_cooldown' => $user->isInOtpCooldown(),
            'remaining_cooldown' => $user->getRemainingCooldownTime(),
            'is_otp_valid' => $user->isOtpStillValid(),
            'remaining_validity' => $user->getRemainingOtpValidity(),
            'can_send_otp' => $user->canSendOtp(),
            'remaining_wait' => $user->getRemainingWaitTime(),
        ]);
    }
}