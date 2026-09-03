<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\WebhookController;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password',[AuthController::class, 'forgotPassword']);

        Route::middleware('auth:sanctum')->group(function () {

            Route::post('/logout',[AuthController::class, 'logout']);

        });
    });

     Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('purchase')->group(function () {
            Route::post('/',[PurchaseController::class, 'store']);
        });
     });

    Route::get('settings',[SettingController::class, 'index']);

    Route::post('webhook',[WebhookController::class, 'handle']
    );
});
