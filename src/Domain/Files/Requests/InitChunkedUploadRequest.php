<?php
namespace Domain\Files\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitChunkedUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filename'     => 'required|string|max:255',
            'filesize'     => 'required|integer|min:1',
            'mimetype'     => 'sometimes|string',
            'parent_id'    => 'sometimes|uuid',
            'path'         => 'sometimes|string',
        ];
    }
}