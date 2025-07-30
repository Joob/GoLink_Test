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
            'email_confirmation' => 'required|email'
        ]);

        $user = Auth::user();

        // 1. Verificar email - usar o email do usuário
        $userEmail = $user->email;
        
        // Debug: Log what we're comparing
        \Log::info('Delete Account Debug', [
            'user_id' => $user->id,
            'expected_email' => $userEmail,
            'received_email' => $request->email_confirmation,
            'user_email' => $user->email
        ]);
        
        if ($request->email_confirmation !== $userEmail) {
            return response()->json([
                'message' => 'Email não coincide',
                'debug' => [
                    'expected' => $userEmail,
                    'received' => $request->email_confirmation
                ]
            ], 422);
        }

        DB::beginTransaction();

        try {
            \Log::info('Starting user account deletion process for user: ' . $user->id);

            // 1. Delete all user files first (including physical files)
            $userFiles = $user->files()->withTrashed()->get();
            foreach ($userFiles as $file) {
                \Log::info('Deleting file: ' . $file->id . ' - ' . $file->basename);
                
                // Delete physical file from storage
                if (Storage::exists("/files/{$file->user_id}/{$file->basename}")) {
                    Storage::delete("/files/{$file->user_id}/{$file->basename}");
                    \Log::info('Physical file deleted: ' . $file->basename);
                }

                // Delete thumbnail if exists
                if ($file->thumbnail) {
                    collect([
                        config('vuefilemanager.image_sizes.later'),
                        config('vuefilemanager.image_sizes.immediately'),
                    ])->collapse()
                        ->each(function ($size) use ($file) {
                            $thumbnailPath = "/files/{$file->user_id}/{$size['name']}-{$file->basename}";
                            if (Storage::exists($thumbnailPath)) {
                                Storage::delete($thumbnailPath);
                            }
                        });
                }

                // Force delete from database
                $file->forceDelete();
            }

            // 2. Delete all user folders
            $userFolders = $user->folders()->withTrashed()->get();
            foreach ($userFolders as $folder) {
                \Log::info('Deleting folder: ' . $folder->id . ' - ' . $folder->name);
                $folder->forceDelete();
            }

            // 3. Delete user directory from storage
            if (Storage::exists("files/{$user->id}")) {
                Storage::deleteDirectory("files/{$user->id}");
                \Log::info('User directory deleted: files/' . $user->id);
            }

            // 4. Delete user associations
            // Delete shares
            if (method_exists($user, 'shares') && $user->shares()->exists()) {
                $user->shares()->delete();
                \Log::info('User shares deleted');
            }
            
            // Delete team invitations
            if (method_exists($user, 'teamInvitations') && $user->teamInvitations()->exists()) {
                $user->teamInvitations()->delete();
                \Log::info('User team invitations deleted');
            }
            
            // Delete tokens
            if (method_exists($user, 'tokens') && $user->tokens()->exists()) {
                $user->tokens()->delete();
                \Log::info('User tokens deleted');
            }
            
            // Delete settings
            if ($user->settings()->exists()) {
                $user->settings()->delete();
                \Log::info('User settings deleted');
            }
            
            // 5. Finally delete the user account
            $user->delete();
            \Log::info('User account deleted: ' . $user->id);

            DB::commit();
            
            // 6. Logout using proper method from session
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

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