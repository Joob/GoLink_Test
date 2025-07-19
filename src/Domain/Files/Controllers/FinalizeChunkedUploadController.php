<?php
namespace Domain\Files\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Domain\Files\Requests\FinalizeChunkedUploadRequest;
use Domain\Files\Actions\FinalizeChunkedUploadAction;

class FinalizeChunkedUploadController extends Controller
{
    public function __construct(
        public FinalizeChunkedUploadAction $finalizeChunkedUpload,
    ) {
    }

    public function __invoke(
        FinalizeChunkedUploadRequest $request
    ): JsonResponse {
        $file = ($this->finalizeChunkedUpload)($request);

        return response()->json($file, 201);
    }
}