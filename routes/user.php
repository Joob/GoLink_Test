<?php

use App\Users\Controllers\Account\UpdateAvatarController;
use App\Users\Controllers\Account\AccountDetailsController;
use App\Users\Controllers\Account\UpdatePasswordController;
use App\Users\Controllers\Account\StorageCapacityController;
use App\Users\Controllers\Authentication\OTPManager;
use App\Users\Controllers\Verification\VerifyEmailController;
use Domain\Transactions\Controllers\GetTransactionsController;
use App\Users\Controllers\Authentication\CheckAccountController;
use App\Users\Controllers\Account\UpdateProfileSettingsController;
use App\Users\Controllers\Authentication\AccountAccessTokenController;
use App\Users\Controllers\Verification\ResendVerificationEmailController;

Route::post('/check', CheckAccountController::class);

// Send OTP Code
//Route::post('/send-otp-code', [OTPManager::class, 'resendOtpCode'])
//    ->middleware('throttle:5,1'); // 5 attempts per minute
//Route::post('/validate-otp-code', [OTPManager::class, 'validateOtpCode'])
//    ->middleware('throttle:10,1'); // 10 attempts per minute

// Add this route
Route::middleware('auth:sanctum')->group(function () {
    // Send OTP Code
    Route::post('/send-otp-code', [OTPManager::class, 'resendOtpCode'])
        ->middleware('throttle:5,1');
    
    // Validate OTP Code  
    Route::post('/validate-otp-code', [OTPManager::class, 'validateOtpCode'])
        ->middleware('throttle:10,1');
        
    // Get OTP Status
    Route::get('/otp-status', [OTPManager::class, 'getOtpStatus']);
});
// Email verification
Route::get('/verify/{id}', VerifyEmailController::class)
    ->name('verification.verify');

Route::post('/verify', ResendVerificationEmailController::class)
    ->name('verification.send');

Route::group(['middleware' => ['auth:sanctum']], function () {
    // User Access Token
    Route::apiResource('/tokens', AccountAccessTokenController::class);

    // Account
    Route::patch('/settings', UpdateProfileSettingsController::class);
    Route::get('/transactions', GetTransactionsController::class);
    Route::post('/password', UpdatePasswordController::class);
    Route::get('/storage', StorageCapacityController::class);
    Route::post('/avatar', UpdateAvatarController::class);
    Route::get('/', AccountDetailsController::class);
});