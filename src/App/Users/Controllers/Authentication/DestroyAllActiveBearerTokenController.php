<?php
namespace App\Users\Controllers\Authentication;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\Sanctum;

class DestroyAllActiveBearerTokenController extends Controller
{
    /*public function logoutAllSessions(Request $request)
    {
        // Validate the password
        $user = Auth::user();
        $password = $request->input('password');

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
            ], 403);
        }

        // Log out the user from all sessions
        //Auth::logoutOtherDevices($password);
        Auth::logoutOtherDevices($request->password);

        // Get the user's session ID
        $sessionId = Auth::guard('web')->getSession()->getId();

        // Invalidate the user's session
        Session::getHandler()->destroy($sessionId);

        // Regenerate a new session ID
        Session::regenerate();

        return response()->json([
            'type'    => 'success',
            'message' => 'You were successfully logged out from all sessions',
        ]);
    }*/

    public function logoutAllSessions(Request $request) {
        // Validate the password
        $user = Auth::user();
        $password = $request->input('password');
    
        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
            ], 403);
        }
    
        // Perform logout logic for all sessions of the user
        Auth::logoutOtherDevices($password);
    
        // Invalidate and regenerate the current session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return response()->json([
            'type'    => 'success',
            'message' => 'You were successfully logged out from all sessions',
        ]);
    }
}
