<?php

use Domain\Zip\Controllers\ZipController;
use Domain\Sharing\Controllers\ShareController;
use Domain\Pages\Controllers\ShowPageController;
use Domain\Trash\Controllers\DumpTrashController;
use App\Users\Controllers\ResetPasswordController;
use Domain\Files\Controllers\UploadFileController;
use App\Users\Controllers\ForgotPasswordController;
use Domain\Folders\Controllers\FavouriteController;
use Domain\Sharing\Controllers\ShareItemController;
use Domain\Settings\Controllers\GetConfigController;
use Domain\SetupWizard\Controllers\PingAPIController;
use Domain\Browsing\Controllers\BrowseTrashController;
use Domain\Folders\Controllers\CreateFolderController;
use Domain\Browsing\Controllers\BrowseFolderController;
use Domain\Browsing\Controllers\BrowseSharedController;
use Domain\Sharing\Controllers\ShareViaEmailController;
use Domain\Sharing\Controllers\GetPasswordNoteController;
use Domain\Files\Controllers\UploadFileChunksController;
use Domain\Folders\Controllers\NavigationTreeController;
use Domain\Items\Controllers\MoveFileOrFolderController;
use App\Socialite\Controllers\SocialiteRedirectController;
use Domain\Browsing\Controllers\SpotlightSearchController;
use Domain\Items\Controllers\DeleteFileOrFolderController;
use Domain\Items\Controllers\RenameFileOrFolderController;
use Domain\Settings\Controllers\GetSettingsValueController;
use Domain\Trash\Controllers\RestoreTrashContentController;
use Domain\Browsing\Controllers\BrowseLatestFilesController;
use Domain\Homepage\Controllers\SendContactMessageController;
use Domain\RemoteUpload\Controllers\RemoteUploadFileController;
use Domain\Sharing\Controllers\GetShareLinkViaQrCodeController;
use App\Users\Controllers\Authentication\RegisterUserController;
use Domain\Notifications\Controllers\GetUserNotificationsController;
use App\Console\Commands\SetupDevEnvironment;
use Domain\Notifications\Controllers\FlushUserNotificationsController;
use Domain\Notifications\Controllers\MarkUserNotificationsAsReadController;
use App\Users\Controllers\Authentication\DestroyActiveBearerTokenController;
use App\Users\Controllers\Authentication\DestroyLogoutController;
use App\Users\Controllers\Authentication\ResetCSRFIDController;
use App\Users\Controllers\Authentication\NotificationController;
use App\Users\Controllers\Account\MoveToTrashController;
use App\Users\Controllers\Authentication\DeleteAccountController;
use App\Users\Controllers\Authentication\AuthenticateAndReturnBearerTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Users\Models\User;
use App\Users\Models\UserLimitation;
use App\Http\Controllers\CounterController;


// Ping Pong
Route::get('/ping', PingAPIController::class);
Route::get('/config', GetConfigController::class);

// Delete Notification
Route::post('/notifications/{id}/delete', [NotificationController::class, 'destroy']) 
    ->middleware('auth:sanctum');

// Delete Account/Files/Folders
Route::post('/trash/move', [MoveToTrashController::class, 'move'])
    ->middleware('auth:sanctum');
Route::delete('/user/account', [DeleteAccountController::class, 'destroy'])
    ->middleware(['web', 'auth']);
Route::get('/user/account/progress', [DeleteAccountController::class, 'getProgress'])
    ->middleware(['web', 'auth']);

// Pages
Route::get('/page/{page}', ShowPageController::class);
Route::middleware(['web'])->group(function () {
    Route::get('/counters', [CounterController::class, 'getCounters']);
});

//Show Password Note
Route::post('/getShowPW', [GetPasswordNoteController::class, 'showPWEditShare']);
Route::post('/showPW', [GetPasswordNoteController::class, 'showPW']);
Route::post('/allowed', [GetPasswordNoteController::class, 'allowed']);

// Homepage
Route::post('/contact', SendContactMessageController::class);
Route::get('/settings', GetSettingsValueController::class);

// Register/login user
Route::post('/register', RegisterUserController::class);
Route::post('/login', AuthenticateAndReturnBearerTokenController::class)
    ->middleware('throttle:sign-in'); 
Route::post('/logout', DestroyLogoutController::class);
Route::post('/ResetCSRF', [ResetCSRFIDController::class, '__invoke'])
    ->middleware('auth:sanctum');

// Socialite
Route::get('/socialite/{provider}/redirect', SocialiteRedirectController::class);

// Password reset
Route::group(['prefix' => 'password'], function () {
    Route::post('/recover', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('/reset', [ResetPasswordController::class, 'reset']);
});

// User master Routes
Route::group(['middleware' => ['auth:sanctum']], function () {

    //Get the Limitation info of user
    Route::post('/user/info', function (Request $request) {
        $user = User::find($request->id);
        return \Response::json($user->limitations);
    });

    //Set the Limitation info of user
    Route::patch('/user/set/{id}', function (Request $request, $id) {
        $validatedData = $request->validate([
            'name' => 'required|string|in:max_team_members',
            'value' => 'required|integer',
        ]);
    
        $user = User::find($id);
    
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
    
        $limitations = DB::table('user_limitations')
            ->where('user_id', $user->id)
            ->update([$validatedData['name'] => $validatedData['value']]);
    
        return response()->json([
            'message' => 'User limitations updated successfully',
            'data' => $validatedData,
        ]);
    });

    //Set the Limitation info of user
    Route::patch('/share/settings', function (Request $request) {
        $limitations = DB::table('shares')->where('user_id',auth('sanctum')->user()->id)->update([$request->name=>$request->value]);
        return \Response::json(true);
    });

    Route::group(['prefix' => 'browse'], function () {
        Route::get('/folders/{id}', BrowseFolderController::class);
        Route::get('/navigation', NavigationTreeController::class);
        Route::get('/latest', BrowseLatestFilesController::class);
        Route::get('/trash/{id}', BrowseTrashController::class);
        Route::get('/share', BrowseSharedController::class);
    });

    // Trash
    Route::group(['prefix' => 'trash'], function () {
        Route::post('/restore', RestoreTrashContentController::class);
        Route::delete('/dump', DumpTrashController::class);
    });

    // Share
    Route::get('/share/{token}/qr', GetShareLinkViaQrCodeController::class);
    Route::post('/share/{token}/email', ShareViaEmailController::class);
    Route::apiResource('/share', ShareController::class);
    Route::post('/share', ShareItemController::class);

    // Notifications
    Route::post('/notifications/read', MarkUserNotificationsAsReadController::class);
    Route::delete('/notifications', FlushUserNotificationsController::class);
    Route::get('/notifications', GetUserNotificationsController::class);
    Route::post('/new-notifications', [SetupDevEnvironment::class, 'generateSendNotification'])
        ->middleware(['auth', 'admin']);

    // Favourites
    Route::apiResource('/favourites', FavouriteController::class);

    // Search
    Route::get('/search', SpotlightSearchController::class);
});

// User master,editor routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/upload/remote', RemoteUploadFileController::class);
    Route::post('/upload/chunks', UploadFileChunksController::class);
    Route::post('/upload', UploadFileController::class);

    Route::post('/create-folder', CreateFolderController::class);

    Route::patch('/rename/{id}', RenameFileOrFolderController::class);
    Route::post('/remove', DeleteFileOrFolderController::class);
    Route::post('/move', MoveFileOrFolderController::class);

    Route::get('/zip', ZipController::class);
});
