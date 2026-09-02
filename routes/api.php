<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;


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


    Route::post('/purchase/webhook',[PurchaseWebhookController::class, 'handle']
    );
});
