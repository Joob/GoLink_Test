<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Excluir uma notificação específica
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        
        try {
            $notification->delete();
            
            return response()->json([
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unable to delete notification',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
