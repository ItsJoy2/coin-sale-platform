<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');

Route::get('login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('login', [AuthController::class, 'login'])->name('admin.login.submit');

Route::prefix('admin')->middleware('admin.auth')->name('admin.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [AuthController::class, 'profile'])->name('index');
        Route::put('/', [AuthController::class, 'profileUpdate'])->name('update');
        Route::put('password', [AuthController::class, 'changePassword'])->name('password.update');
    });

    Route::prefix('users')->name('users.')->group(function () {

        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::put('/{user}/password', [UserController::class, 'updatePassword'])->name('password');
        Route::post('/{user}/balance-adjust', [UserController::class, 'adjustBalance'])->name('balance.adjust');
    });
        Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [HistoryController::class, 'purchase'])->name('index');
        Route::put('/{purchase}/status', [HistoryController::class, 'updatePurchaseStatus'])->name('status');
    });
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [HistoryController::class, 'transactions'])->name('index');
    });

    Route::prefix('settings')->name('settings.')->group(function () {

        Route::get('/', [ SettingsController::class, 'index' ])->name('index');
        Route::put('/', [ SettingsController::class, 'update' ])->name('update');

    });
    Route::prefix('coupons')->name('coupons.')->group(function () {

        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
        Route::patch('/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

});
