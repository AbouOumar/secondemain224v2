<?php
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ArticleModerationController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Admin\DeliveryController as AdminDeliveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend']);
    Route::post('users/{user}/activate', [UserController::class, 'activate']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::get('articles', [ArticleModerationController::class, 'index']);
    Route::post('articles/{article}/verify', [ArticleModerationController::class, 'verify']);
    Route::post('articles/{article}/reject', [ArticleModerationController::class, 'reject']);
    Route::apiResource('categories', AdminCategoryController::class);
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);
    Route::get('partners', [AdminPartnerController::class, 'index']);
    Route::get('partners/{partner}', [AdminPartnerController::class, 'show']);
    Route::post('partners/{partner}/verify', [AdminPartnerController::class, 'verify']);
    Route::post('partners/{partner}/unverify', [AdminPartnerController::class, 'unverify']);
    Route::delete('partners/{partner}', [AdminPartnerController::class, 'destroy']);
    Route::get('deliveries', [AdminDeliveryController::class, 'index']);
    Route::get('deliveries/{delivery}', [AdminDeliveryController::class, 'show']);
    Route::put('deliveries/{delivery}', [AdminDeliveryController::class, 'update']);
});
