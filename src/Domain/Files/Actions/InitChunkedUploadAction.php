<?php
namespace Domain\Files\Actions;

use Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Domain\Files\Models\UploadSession;
use Domain\Files\Requests\InitChunkedUploadRequest;

class InitChunkedUploadAction
{
    public function __invoke(InitChunkedUploadRequest $request)
    {
        // Generate unique session ID
        $sessionId = Str::uuid();
        
        // Create upload session record
        $session = UploadSession::create([
            'id' => $sessionId,
            'user_id' => auth()->id(),
            'filename' => $request->input('filename'),
            'filesize' => $request->input('filesize'),
            'mimetype' => $request->input('mimetype'),
            'parent_id' => $request->input('parent_id'),
            'path' => $request->input('path'),
            'chunks_uploaded' => 0,
            'total_chunks' => 0,
            'expires_at' => now()->addHours(24), // 24 hour expiry
            'status' => 'initialized'
        ]);

        // Create session directory for chunks
        $sessionPath = "chunks/sessions/{$sessionId}";
        Storage::disk('local')->makeDirectory($sessionPath);

        return $session;
    }
}