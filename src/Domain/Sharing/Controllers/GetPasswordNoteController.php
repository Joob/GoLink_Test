<?php
namespace Domain\Sharing\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Domain\Sharing\Models\Share;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class GetPasswordNoteController extends Controller
{   

    public function showPWEditShare(): JsonResponse
    {
        $latestShare = DB::table('shares')->latest()->first();
    
        if ($latestShare) {
            $showPasswordIn = $latestShare->showpassword_in;
        } else {
            $showPasswordIn = '';
        }
    
        return response()->json(['showpassword_in' => $showPasswordIn]);
    }

    public function showPW(Request $request) {
        $data = $request->all();
        $item = Share::where('token', $data['token'])->first();
        return $item->user;
    }

    public function allowed(Request $request) {
        $data = $request->all();
        $item = Share::where('token', $data['token'])->first();
        return $item;
    }
}
