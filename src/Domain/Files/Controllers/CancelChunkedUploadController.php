<?php
namespace Domain\Files\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Domain\Files\Requests\CancelChunkedUploadRequest;
use Domain\Files\Actions\CancelChunkedUploadAction;

class CancelChunkedUploadController extends Controller
{
    public function __construct(
        public CancelChunkedUploadAction $cancelChunkedUpload,
    ) {
    }

    public function __invoke(
        CancelChunkedUploadRequest $request
    ): JsonResponse {
        ($this->cancelChunkedUpload)($request);

        return response()->json([
            'message' => 'Upload cancelled successfully'
        ], 200);
    }
}