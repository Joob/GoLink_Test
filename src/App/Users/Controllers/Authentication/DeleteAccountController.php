<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAccountController extends Controller
{
    /**
     * Delete user account and all associated data
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'username_confirmation' => 'required|string'
        ]);

        $user = Auth::user();

        // 1. Verificar nome - usar o nome das settings ou email como fallback
        $userName = $user->settings->name ?? $user->email;
        
        // Debug: Log what we're comparing
        \Log::info('Delete Account Debug', [
            'user_id' => $user->id,
            'expected_username' => $userName,
            'received_username' => $request->username_confirmation,
            'settings_name' => $user->settings ? $user->settings->name : null,
            'settings_first_name' => $user->settings ? $user->settings->first_name : null,
            'settings_last_name' => $user->settings ? $user->settings->last_name : null,
            'user_email' => $user->email
        ]);
        
        if ($request->username_confirmation !== $userName) {
            return response()->json([
                'message' => 'Nome de utilizador não coincide',
                'debug' => [
                    'expected' => $userName,
                    'received' => $request->username_confirmation
                ]
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 2. Mover todos os ficheiros e pastas do usuário para o trash
            $fileIds = $user->files()->pluck('id')->toArray();
            $folderIds = $user->folders()->pluck('id')->toArray();

            if (!empty($fileIds) || !empty($folderIds)) {
                // Chama o controller de mover para trash (ajuste conforme seu controller real)
                app(\App\Users\Controllers\Account\MoveToTrashController::class)->move([
                    'files' => $fileIds,
                    'folders' => $folderIds,
                    'user_id' => $user->id
                ]);
            }

            // 3. Chamar o dump da lixeira (apaga definitivamente)
            // Simula uma request DELETE no endpoint da lixeira
            $requestTrash = Request::create('/api/trash/dump', 'DELETE');
            app()->handle($requestTrash);

            // 4. Apagar o registro do usuário e associações
            $user->shares()->delete();
            $user->teamInvitations()->delete();
            $user->tokens()->delete();
            $user->settings()->delete();
            $user->delete();

            DB::commit();
            Auth::logout();

            return response()->json([
                'message' => 'Conta apagada com sucesso'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting user account: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao apagar conta. Por favor, tente novamente.'
            ], 500);
        }
    }
}