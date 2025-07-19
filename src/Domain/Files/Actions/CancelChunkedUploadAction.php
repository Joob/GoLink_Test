<?php
namespace Domain\Files\Actions;

use Storage;
use Domain\Files\Models\UploadSession;
use Domain\Files\Requests\CancelChunkedUploadRequest;

class CancelChunkedUploadAction
{
    public function __invoke(CancelChunkedUploadRequest $request)
    {
        $sessionId = $request->input('session_id');
        
        // Get upload session
        $session = UploadSession::find($sessionId);
        
        if ($session) {
            // Verify session belongs to authenticated user
            if ($session->user_id === auth()->id()) {
                // Update session status
                $session->update(['status' => 'cancelled']);
                
                // Cleanup chunks
                $this->cleanupSession($sessionId);
            }
        }
    }

    private function cleanupSession(string $sessionId): void
    {
        $sessionPath = "chunks/sessions/{$sessionId}";
        
        // Delete all chunk files and session directory
        if (Storage::disk('local')->exists($sessionPath)) {
            Storage::disk('local')->deleteDirectory($sessionPath);
        }
    }
}