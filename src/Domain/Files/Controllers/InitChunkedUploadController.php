<?php
namespace Domain\Files\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Domain\Files\Requests\InitChunkedUploadRequest;
use Domain\Files\Actions\InitChunkedUploadAction;

class InitChunkedUploadController extends Controller
{
    public function __construct(
        public InitChunkedUploadAction $initChunkedUpload,
    ) {
    }

    public function __invoke(
        InitChunkedUploadRequest $request
    ): JsonResponse {
        $session = ($this->initChunkedUpload)($request);

        return response()->json([
            'session_id' => $session->id,
            'chunk_size' => config('app.chunk_size', 1024 * 1024), // 1MB default
            'expires_at' => $session->expires_at,
        ], 201);
    }
}