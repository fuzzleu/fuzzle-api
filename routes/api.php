<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/signup', [AuthController::class, 'signUp']);
    Route::post('/signin', [AuthController::class, 'signIn']);
    Route::post('/reset-password', [PasswordResetController::class, 'ForgotPassword']);
    Route::post('/reset-password/{token}', [PasswordResetController::class, 'ResetPassword']);
    Route::get('/reset-password/{token}/remove', [PasswordResetController::class, 'RemoveRequestPassword']);
    Route::group(['middleware' => 'auth'], function () {
        Route::post('/signout', [AuthController::class, 'signOut']);
        Route::get('/refresh', [AuthController::class, 'refreshToken']);
    });
});

Route::group(['prefix' => 'users', 'middleware' => 'auth'], function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/me/avatar', [UserController::class, 'uploadAvatar']);
    Route::patch('/me', [UserController::class, 'updateMe']);
});
Route::apiResource('users', UserController::class);
