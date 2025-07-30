<?php

namespace App\Users\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class DeleteAccountController extends Controller
{
    /**
     * Get deletion progress
     */
    public function getProgress(Request $request)
    {
        $user = Auth::user();
        $progressKey = "delete_account_progress_{$user->id}";
        
        $progress = Cache::get($progressKey, [
            'percentage' => 0,
            'current_step' => 'Preparando eliminação...',
            'completed' => false
        ]);
        
        return response()->json($progress);
    }

    /**
     * Update deletion progress
     */
    private function updateProgress($userId, $percentage, $currentStep, $completed = false)
    {
        $progressKey = "delete_account_progress_{$userId}";
        
        $progress = [
            'percentage' => $percentage,
            'current_step' => $currentStep,
            'completed' => $completed
        ];
        
        Cache::put($progressKey, $progress, now()->addMinutes(10));
        \Log::info("Progress updated: {$percentage}% - {$currentStep}");
    }

    /**
     * Delete user account and all associated data
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'email_confirmation' => 'required|email'
        ]);

        $user = Auth::user();
        $userId = $user->id;

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

        // Initialize progress tracking
        $this->updateProgress($userId, 0, 'Iniciando eliminação da conta...');

        DB::beginTransaction();

        try {
            \Log::info('Starting user account deletion process for user: ' . $user->id);
            $this->updateProgress($userId, 5, 'A preparar eliminação de ficheiros...');

            // 1. Delete all user files first (including physical files)
            $userFiles = $user->files()->withTrashed()->get();
            $totalFiles = $userFiles->count();
            $filesDeleted = 0;
            
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
                $filesDeleted++;
                
                // Update progress for files (5% to 50%)
                if ($totalFiles > 0) {
                    $fileProgress = 5 + (($filesDeleted / $totalFiles) * 45);
                    $this->updateProgress($userId, $fileProgress, "A eliminar ficheiros... ({$filesDeleted}/{$totalFiles})");
                }
            }

            $this->updateProgress($userId, 50, 'A eliminar pastas...');

            // 2. Delete all user folders
            $userFolders = $user->folders()->withTrashed()->get();
            $totalFolders = $userFolders->count();
            $foldersDeleted = 0;
            
            foreach ($userFolders as $folder) {
                \Log::info('Deleting folder: ' . $folder->id . ' - ' . $folder->name);
                $folder->forceDelete();
                $foldersDeleted++;
                
                // Update progress for folders (50% to 60%)
                if ($totalFolders > 0) {
                    $folderProgress = 50 + (($foldersDeleted / $totalFolders) * 10);
                    $this->updateProgress($userId, $folderProgress, "A eliminar pastas... ({$foldersDeleted}/{$totalFolders})");
                }
            }

            $this->updateProgress($userId, 60, 'A eliminar diretório do utilizador...');

            // 3. Delete user directory from storage
            if (Storage::exists("files/{$user->id}")) {
                Storage::deleteDirectory("files/{$user->id}");
                \Log::info('User directory deleted: files/' . $user->id);
            }

            $this->updateProgress($userId, 70, 'A eliminar partilhas...');

            // 4. Delete user associations
            // Delete shares
            if (method_exists($user, 'shares') && $user->shares()->exists()) {
                $user->shares()->delete();
                \Log::info('User shares deleted');
            }
            
            $this->updateProgress($userId, 80, 'A eliminar convites de equipa...');
            
            // Delete team invitations
            if (method_exists($user, 'teamInvitations') && $user->teamInvitations()->exists()) {
                $user->teamInvitations()->delete();
                \Log::info('User team invitations deleted');
            }
            
            $this->updateProgress($userId, 85, 'A eliminar tokens...');
            
            // Delete tokens
            if (method_exists($user, 'tokens') && $user->tokens()->exists()) {
                $user->tokens()->delete();
                \Log::info('User tokens deleted');
            }
            
            $this->updateProgress($userId, 90, 'A eliminar configurações...');
            
            // Delete settings
            if ($user->settings()->exists()) {
                $user->settings()->delete();
                \Log::info('User settings deleted');
            }
            
            $this->updateProgress($userId, 95, 'A eliminar conta...');
            
            // 5. Finally delete the user account
            $user->delete();
            \Log::info('User account deleted: ' . $user->id);

            DB::commit();
            
            $this->updateProgress($userId, 100, 'Conta eliminada com sucesso!', true);
            
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
            $this->updateProgress($userId, 0, 'Erro ao eliminar conta', false);
            return response()->json([
                'message' => 'Erro ao apagar conta. Por favor, tente novamente.'
            ], 500);
        }
    }
}