<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group(['prefix' => 'auth', 'middleware' => 'rate.limit:5'], function () {
    // Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('logout', [AuthController::class, 'logout'])->middleware('token.validate');
    Route::get('me', [AuthController::class, 'me'])->middleware('token.validate');
    
    // Password reset routes with stricter rate limiting
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('rate.limit:3');
    Route::post('verify-otp', [AuthController::class, 'verifyOTP'])->middleware('rate.limit:5');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('rate.limit:3');
}); // max 5 attempts per minute;
