<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/lives/lose', [AuthController::class, 'loseLife']);
    Route::post('/billing/checkout', [AuthController::class, 'createCheckoutSession']);
    Route::post('/billing/confirm', [AuthController::class, 'confirmCheckout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/progress', [AuthController::class, 'updateProgress']);
    Route::get('/ranking', [AuthController::class, 'ranking']);
    Route::get('/subjects', [AuthController::class, 'subjects']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::post('/subjects', [AuthController::class, 'storeSubject']);
        Route::put('/subjects/{subject}', [AuthController::class, 'updateSubject']);
        Route::delete('/subjects/{subject}', [AuthController::class, 'destroySubject']);
        Route::post('/subjects/{subject}/activities', [AuthController::class, 'storeActivity']);
        Route::put('/activities/{activity}', [AuthController::class, 'updateActivity']);
        Route::delete('/activities/{activity}', [AuthController::class, 'destroyActivity']);
    });
});