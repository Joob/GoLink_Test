<?php
namespace App\Console\Commands;

use Storage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Domain\Files\Models\UploadSession;

class CleanupExpiredUploadSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:cleanup-sessions {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired upload sessions and their associated chunk files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting upload session cleanup...');

        // Find expired sessions
        $expiredSessions = UploadSession::where('expires_at', '<', now())
            ->whereNotIn('status', ['completed'])
            ->get();

        if ($expiredSessions->isEmpty()) {
            $this->info('No expired sessions found.');
            return 0;
        }

        $this->info("Found {$expiredSessions->count()} expired sessions to clean up.");

        if (!$this->option('force') && !$this->confirm('Do you want to proceed with cleanup?')) {
            $this->info('Cleanup cancelled.');
            return 0;
        }

        $cleanedSessions = 0;
        $cleanedFiles = 0;

        foreach ($expiredSessions as $session) {
            try {
                // Clean up chunk files
                $sessionPath = "chunks/sessions/{$session->id}";
                
                if (Storage::disk('local')->exists($sessionPath)) {
                    $files = Storage::disk('local')->allFiles($sessionPath);
                    $cleanedFiles += count($files);
                    
                    Storage::disk('local')->deleteDirectory($sessionPath);
                    $this->line("Cleaned chunk files for session: {$session->id}");
                }

                // Update session status
                $session->update(['status' => 'expired']);
                $cleanedSessions++;

            } catch (\Exception $e) {
                $this->error("Failed to clean session {$session->id}: " . $e->getMessage());
            }
        }

        $this->info("Cleanup completed:");
        $this->info("- Sessions processed: {$cleanedSessions}");
        $this->info("- Chunk files removed: {$cleanedFiles}");

        return 0;
    }
}