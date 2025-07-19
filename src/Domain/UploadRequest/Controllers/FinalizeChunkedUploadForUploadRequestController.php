<?php
namespace Domain\UploadRequest\Controllers;

use Domain\Files\Controllers\FinalizeChunkedUploadController;
use Domain\Files\Requests\FinalizeChunkedUploadRequest;
use Domain\UploadRequest\Models\UploadRequest;
use Illuminate\Http\JsonResponse;

class FinalizeChunkedUploadForUploadRequestController extends FinalizeChunkedUploadController
{
    public function __invoke(
        FinalizeChunkedUploadRequest $request,
        UploadRequest $uploadRequest
    ): JsonResponse {
        // Set the upload request context
        $request->merge(['upload_request_id' => $uploadRequest->id]);
        
        return parent::__invoke($request);
    }
}