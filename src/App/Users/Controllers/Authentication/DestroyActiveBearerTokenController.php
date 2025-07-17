<?php
namespace App\Users\Controllers\Authentication;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class DestroyActiveBearerTokenController extends Controller
{
    public function __invoke(): JsonResponse
    {
        [$id, $token] = explode('|', request()->bearerToken(), 2);

        // Get token
        $instance = PersonalAccessToken::findOrFail($id);

        // Check the correct token
        if (hash_equals($instance->token, hash('sha256', $token))) {
            // Delete token
            $instance->delete();

            return response()->json([
                'type'    => 'success',
                'message' => 'You were successfully logged out',
            ]);
        }

        return response()->json([
            'type'    => 'error',
            'message' => 'You are not authorized',
        ], 401);
    }

}
