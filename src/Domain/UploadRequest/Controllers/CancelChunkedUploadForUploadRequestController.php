<?php
namespace Domain\UploadRequest\Controllers;

use Domain\Files\Controllers\CancelChunkedUploadController;
use Domain\Files\Requests\CancelChunkedUploadRequest;
use Domain\UploadRequest\Models\UploadRequest;
use Illuminate\Http\JsonResponse;

class CancelChunkedUploadForUploadRequestController extends CancelChunkedUploadController
{
    public function __invoke(
        CancelChunkedUploadRequest $request,
        UploadRequest $uploadRequest
    ): JsonResponse {
        // Set the upload request context
        $request->merge(['upload_request_id' => $uploadRequest->id]);
        
        return parent::__invoke($request);
    }
}