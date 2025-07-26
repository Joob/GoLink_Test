<?php

namespace App\Users\Controllers\Authentication;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;

class DestroyLogoutController extends Controller
{
    /**
     * Perform complete logout for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'type' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Revoke all tokens for the user (if using Sanctum)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Logout the user
        Auth::guard('web')->logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the session token to prevent session fixation
        $request->session()->regenerateToken();

        // Clear remember me cookie if exists
        if ($request->hasCookie('remember_web')) {
            Cookie::queue(Cookie::forget('remember_web'));
        }

        // Clear any other authentication related cookies
        Cookie::queue(Cookie::forget('laravel_session'));
        Cookie::queue(Cookie::forget('XSRF-TOKEN'));

        return response()->json([
            'type' => 'success',
            'message' => 'You have been successfully logged out',
            'data' => [
                'redirect_url' => route('login') // Optional: provide a redirect URL
            ]
        ]);
    }
}