<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ResetCSRFIDController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'password' => 'required'
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Verify the password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Incorrect password'
                ], 403);
            }

            // Regenerate the CSRF token
            $request->session()->regenerateToken();

            // Get the new CSRF token
            $newToken = csrf_token();

            return response()->json([
                'type' => 'success',
                'message' => 'CSRF token successfully regenerated',
                'data' => [
                    'csrf_token' => $newToken
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('CSRF Reset Error: ' . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
