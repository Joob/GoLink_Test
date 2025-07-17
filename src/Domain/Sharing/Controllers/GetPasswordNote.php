<?php
namespace Domain\Sharing\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Domain\Sharing\Models\Share;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class GetPasswordNote extends Controller
{
    /*public function index()
    {
        $users = DB::table('shares')->select('allow_showpassword_in', 'showpassword_in')->get();
        print_r($users);
        //exit;
        return $users;
         
    }*/

    /*public function index(
      Share $share,
    ): JsonResponse {
        $users = DB::table('shares')->select('allow_showpassword_in', 'showpassword_in')->get();
        print_r($users);
        //exit;
        return $users;
    }*/

    
    public function showPWEditShare(Request $request) {
        $data = $request->all();
        DB::table('shares')->select($data);
        return Response::json(true);
    }

    /*public function showPW()
    {
        $getShowPW = DB::table('shares')->select('allow_showpassword_in', 'showpassword_in')->get();

        echo "<pre>";
        print_r($getShowPW);
    }*/
}
