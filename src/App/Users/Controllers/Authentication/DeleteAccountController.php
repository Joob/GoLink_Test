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
        
        // Add small delay to ensure progress is visible
        sleep(1);

        DB::beginTransaction();

        try {
            \Log::info('Starting user account deletion process for user: ' . $user->id);
            $this->updateProgress($userId, 5, 'A preparar eliminação de ficheiros...');
            sleep(1);

            // 1. Delete all user files first (including physical files)
            $userFiles = $user->files()->withTrashed()->get();
            $totalFiles = $userFiles->count();
            $filesDeleted = 0;
            
            \Log::info("Found {$totalFiles} files to delete for user {$user->id}");
            
            foreach ($userFiles as $file) {
                \Log::info('Deleting file: ' . $file->id . ' - ' . $file->basename);
                
                // Delete physical file from storage
                $filePath = "/files/{$file->user_id}/{$file->basename}";
                if (Storage::exists($filePath)) {
                    $deleted = Storage::delete($filePath);
                    \Log::info("Physical file deletion result for {$file->basename}: " . ($deleted ? 'SUCCESS' : 'FAILED'));
                } else {
                    \Log::warning("Physical file not found: {$filePath}");
                }

                // Delete thumbnail if exists
                if ($file->thumbnail) {
                    $thumbnailsDeleted = 0;
                    collect([
                        config('vuefilemanager.image_sizes.later'),
                        config('vuefilemanager.image_sizes.immediately'),
                    ])->collapse()
                        ->each(function ($size) use ($file, &$thumbnailsDeleted) {
                            $thumbnailPath = "/files/{$file->user_id}/{$size['name']}-{$file->basename}";
                            if (Storage::exists($thumbnailPath)) {
                                $deleted = Storage::delete($thumbnailPath);
                                if ($deleted) $thumbnailsDeleted++;
                                \Log::info("Thumbnail deletion result for {$size['name']}-{$file->basename}: " . ($deleted ? 'SUCCESS' : 'FAILED'));
                            }
                        });
                    \Log::info("Deleted {$thumbnailsDeleted} thumbnails for {$file->basename}");
                }

                // Force delete from database
                $file->forceDelete();
                $filesDeleted++;
                
                // Update progress for files (5% to 50%) with small delay to make progress visible
                if ($totalFiles > 0) {
                    $fileProgress = 5 + (($filesDeleted / $totalFiles) * 45);
                    $this->updateProgress($userId, $fileProgress, "A eliminar ficheiros... ({$filesDeleted}/{$totalFiles})");
                    
                    // Add small delay every few files to make progress visible
                    if ($filesDeleted % 3 == 0) {
                        usleep(500000); // 0.5 seconds
                    }
                }
            }

            $this->updateProgress($userId, 50, 'A eliminar pastas...');
            sleep(1);

            // 2. Delete all user folders
            $userFolders = $user->folders()->withTrashed()->get();
            $totalFolders = $userFolders->count();
            $foldersDeleted = 0;
            
            \Log::info("Found {$totalFolders} folders to delete for user {$user->id}");
            
            foreach ($userFolders as $folder) {
                \Log::info('Deleting folder: ' . $folder->id . ' - ' . $folder->name);
                $folder->forceDelete();
                $foldersDeleted++;
                
                // Update progress for folders (50% to 60%)
                if ($totalFolders > 0) {
                    $folderProgress = 50 + (($foldersDeleted / $totalFolders) * 10);
                    $this->updateProgress($userId, $folderProgress, "A eliminar pastas... ({$foldersDeleted}/{$totalFolders})");
                    usleep(300000); // 0.3 seconds
                }
            }

            $this->updateProgress($userId, 60, 'A eliminar diretório do utilizador...');
            sleep(1);

            // 3. Delete user directory from storage
            $userDirectoryPath = "files/{$user->id}";
            if (Storage::exists($userDirectoryPath)) {
                $deleted = Storage::deleteDirectory($userDirectoryPath);
                \Log::info("User directory deletion result for files/{$user->id}: " . ($deleted ? 'SUCCESS' : 'FAILED'));
                
                // Double-check directory deletion
                if (Storage::exists($userDirectoryPath)) {
                    \Log::warning("User directory still exists after deletion attempt: files/{$user->id}");
                } else {
                    \Log::info('User directory completely removed: files/' . $user->id);
                }
            } else {
                \Log::info("User directory not found: files/{$user->id}");
            }

            $this->updateProgress($userId, 70, 'A eliminar partilhas...');
            sleep(1);

            // 4. Delete user associations
            // Delete shares
            if (method_exists($user, 'shares') && $user->shares()->exists()) {
                $sharesCount = $user->shares()->count();
                $user->shares()->delete();
                \Log::info("User shares deleted: {$sharesCount} shares");
            }
            
            $this->updateProgress($userId, 80, 'A eliminar convites de equipa...');
            sleep(1);
            
            // Delete team invitations
            if (method_exists($user, 'teamInvitations') && $user->teamInvitations()->exists()) {
                $invitationsCount = $user->teamInvitations()->count();
                $user->teamInvitations()->delete();
                \Log::info("User team invitations deleted: {$invitationsCount} invitations");
            }
            
            $this->updateProgress($userId, 85, 'A eliminar tokens...');
            sleep(1);
            
            // Delete tokens
            if (method_exists($user, 'tokens') && $user->tokens()->exists()) {
                $tokensCount = $user->tokens()->count();
                $user->tokens()->delete();
                \Log::info("User tokens deleted: {$tokensCount} tokens");
            }
            
            $this->updateProgress($userId, 90, 'A eliminar configurações...');
            sleep(1);
            
            // Delete settings
            if ($user->settings()->exists()) {
                $user->settings()->delete();
                \Log::info('User settings deleted');
            }
            
            $this->updateProgress($userId, 95, 'A eliminar conta...');
            sleep(1);
            
            // 5. Finally delete the user account
            $user->delete();
            \Log::info('User account deleted: ' . $user->id);

            DB::commit();
            
            $this->updateProgress($userId, 100, 'Conta eliminada com sucesso!', true);
            
            // Add delay before logout to ensure progress is shown
            sleep(2);
            
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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->updateProgress($userId, 0, 'Erro ao eliminar conta', false);
            return response()->json([
                'message' => 'Erro ao apagar conta. Por favor, tente novamente.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}