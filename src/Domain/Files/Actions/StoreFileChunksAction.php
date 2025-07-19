<?php
namespace Domain\Files\Actions;

use Storage;
use Illuminate\Support\Facades\File;
use Domain\Files\Models\UploadSession;
use Domain\Files\Requests\UploadChunkRequest;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class StoreFileChunksAction
{
    /**
     * @throws FileNotFoundException
     */
    public function __invoke(UploadChunkRequest $request)
    {
        // Get uploaded file
        $file = $request->file('chunk');
        
        // Check if we have a session ID for enhanced chunked upload
        if ($request->has('session_id')) {
            return $this->storeSessionBasedChunk($request, $file);
        }
        
        // Legacy chunk handling (backward compatibility)
        return $this->storeLegacyChunk($request, $file);
    }

    private function storeSessionBasedChunk(UploadChunkRequest $request, $file)
    {
        $sessionId = $request->input('session_id');
        $chunkIndex = $request->input('chunk_index', 0);
        
        // Get upload session
        $session = UploadSession::findOrFail($sessionId);
        
        // Verify session belongs to authenticated user
        if ($session->user_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to upload session');
        }
        
        // Verify session is not expired
        if ($session->expires_at < now()) {
            throw new \Exception('Upload session has expired');
        }

        // Store chunk in session directory
        $sessionPath = "chunks/sessions/{$sessionId}";
        $chunkPath = "{$sessionPath}/{$chunkIndex}";
        
        // Ensure session directory exists
        Storage::disk('local')->makeDirectory($sessionPath);
        
        // Store the chunk
        Storage::disk('local')->put($chunkPath, $file->get());
        
        // Update session progress
        $session->increment('chunks_uploaded');
        
        // Calculate total chunks if not set
        if ($session->total_chunks === 0) {
            $chunkSize = config('app.chunk_size', 1024 * 1024);
            $session->update([
                'total_chunks' => ceil($session->filesize / $chunkSize)
            ]);
        }
        
        // Update session status
        if ($session->chunks_uploaded >= $session->total_chunks) {
            $session->update(['status' => 'ready_for_merge']);
        }

        return $chunkPath;
    }

    private function storeLegacyChunk(UploadChunkRequest $request, $file)
    {
        // Get chunk name
        $name = $file->getClientOriginalName();

        // Get chunk file path
        $path = Storage::disk('local')->path("chunks/$name");

        // Build the file
        File::append($path, $file->get());

        // If last chunk, then return file path
        if ($request->boolean('is_last_chunk')) {
            return "chunks/$name";
        }
    }
}
