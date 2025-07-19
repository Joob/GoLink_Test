<?php
namespace Domain\Files\Actions;

use Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Domain\Files\Models\UploadSession;
use Domain\Files\Resources\FileResource;
use Domain\Files\Actions\ProcessFileAction;
use Domain\Files\Requests\FinalizeChunkedUploadRequest;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class FinalizeChunkedUploadAction
{
    public function __construct(
        public ProcessFileAction $processFile,
    ) {
    }

    /**
     * @throws FileNotFoundException
     */
    public function __invoke(FinalizeChunkedUploadRequest $request)
    {
        $sessionId = $request->input('session_id');
        
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

        try {
            // Get all chunk files
            $sessionPath = "chunks/sessions/{$sessionId}";
            $chunkFiles = Storage::disk('local')->files($sessionPath);
            
            // Sort chunks numerically
            usort($chunkFiles, function($a, $b) {
                $aNum = (int) pathinfo($a, PATHINFO_FILENAME);
                $bNum = (int) pathinfo($b, PATHINFO_FILENAME);
                return $aNum <=> $bNum;
            });

            // Generate final file name
            $extension = pathinfo($session->filename, PATHINFO_EXTENSION);
            $finalFileName = Str::uuid() . '.' . $extension;
            $finalPath = "files/{$session->user_id}/{$finalFileName}";

            // Merge chunks into final file
            $this->mergeChunks($chunkFiles, $finalPath);

            // Create a mock request for ProcessFileAction
            $mockRequest = new class {
                private $data;
                
                public function __construct() {
                    $this->data = [];
                }
                
                public function input($key, $default = null) {
                    return $this->data[$key] ?? $default;
                }
                
                public function merge($data) {
                    $this->data = array_merge($this->data, $data);
                }
                
                public function filled($key) {
                    return isset($this->data[$key]) && $this->data[$key] !== '';
                }
            };

            // Set request data
            $mockRequest->merge([
                'name' => $session->filename,
                'parent_id' => $session->parent_id,
                'path' => $session->path,
            ]);

            // Process the file
            $file = ($this->processFile)($mockRequest, $session->user, $finalFileName);

            // Update session status
            $session->update([
                'status' => 'completed',
                'file_id' => $file->id
            ]);

            // Cleanup chunks
            $this->cleanupSession($sessionId);

            return new FileResource($file);

        } catch (\Exception $e) {
            // Update session status to failed
            $session->update(['status' => 'failed']);
            
            // Cleanup chunks
            $this->cleanupSession($sessionId);
            
            throw $e;
        }
    }

    private function mergeChunks(array $chunkFiles, string $finalPath): void
    {
        $finalFile = Storage::disk('local')->path($finalPath);
        
        // Ensure directory exists
        File::ensureDirectoryExists(dirname($finalFile));
        
        // Create final file
        $handle = fopen($finalFile, 'wb');
        
        if (!$handle) {
            throw new \Exception('Could not create final file');
        }

        try {
            foreach ($chunkFiles as $chunkFile) {
                $chunkPath = Storage::disk('local')->path($chunkFile);
                $chunkHandle = fopen($chunkPath, 'rb');
                
                if (!$chunkHandle) {
                    throw new \Exception("Could not read chunk file: {$chunkFile}");
                }

                // Copy chunk data to final file
                while (!feof($chunkHandle)) {
                    $data = fread($chunkHandle, 8192); // 8KB buffer
                    fwrite($handle, $data);
                }
                
                fclose($chunkHandle);
            }
        } finally {
            fclose($handle);
        }
    }

    private function cleanupSession(string $sessionId): void
    {
        $sessionPath = "chunks/sessions/{$sessionId}";
        
        // Delete all chunk files and session directory
        Storage::disk('local')->deleteDirectory($sessionPath);
    }
}