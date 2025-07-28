<?php

namespace App\Files\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Domain\Files\Models\File;
use Domain\Folders\Models\Folder;

class MoveToTrashController extends Controller
{
    /**
     * Move all user files and folders to trash
     *
     * @param array $ids ['files' => [], 'folders' => []]
     */
    public function move($ids = [])
    {
        $user = Auth::user();

        // Se chamado internamente, pode receber $ids como parâmetro, senão pega do request
        if (empty($ids)) {
            $ids = [
                'files' => request('files', []),
                'folders' => request('folders', [])
            ];
        }

        // Move os ficheiros para o trash (soft delete)
        if (!empty($ids['files'])) {
            File::whereIn('id', $ids['files'])->where('user_id', $user->id)->delete();
        }

        // Move as pastas para o trash (soft delete)
        if (!empty($ids['folders'])) {
            Folder::whereIn('id', $ids['folders'])->where('user_id', $user->id)->delete();
        }

        return response()->json(['message' => 'Ficheiros e pastas movidos para a lixeira.']);
    }
}