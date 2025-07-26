<?php
namespace Domain\Admin\Controllers\Dashboard;

use App\Users\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Users\Resources\UserResource;

class GetNewbiesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $users = User::sortable([
            'created_at' => 'desc',
        ])
            ->take(5)
            ->get();
        
        // Correção: use UserResource::collection para garantir os campos corretos
        return response()->json(UserResource::collection($users));
    }
}