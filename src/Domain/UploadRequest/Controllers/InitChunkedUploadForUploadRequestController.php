<?php
namespace Domain\UploadRequest\Controllers;

use Domain\Files\Controllers\InitChunkedUploadController;
use Domain\Files\Requests\InitChunkedUploadRequest;
use Domain\UploadRequest\Models\UploadRequest;
use Illuminate\Http\JsonResponse;

class InitChunkedUploadForUploadRequestController extends InitChunkedUploadController
{
    public function __invoke(
        InitChunkedUploadRequest $request,
        UploadRequest $uploadRequest
    ): JsonResponse {
        // Set the upload request context
        $request->merge(['upload_request_id' => $uploadRequest->id]);
        
        return parent::__invoke($request);
    }
}