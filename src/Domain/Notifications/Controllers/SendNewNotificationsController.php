<?php

namespace Domain\Notifications\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\SentNotification;
use App\Http\Controllers\Controller;

class SendNewNotificationsController extends Controller
{
    public function sendNewNotifications(Request $request)
    {
        $message = $request->input('message');

        $notification = new SentNotification();
        $notification->message = $message;
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    }
}
