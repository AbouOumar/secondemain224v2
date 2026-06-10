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
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('users/{id}/suspend', [UserController::class, 'suspend']);
    Route::post('users/{id}/activate', [UserController::class, 'activate']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::get('articles', [ArticleModerationController::class, 'index']);
    Route::post('articles/{id}/verify', [ArticleModerationController::class, 'verify']);
    Route::post('articles/{id}/reject', [ArticleModerationController::class, 'reject']);
    Route::apiResource('categories', AdminCategoryController::class);
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/{id}', [AdminPaymentController::class, 'show']);
    Route::get('partners', [AdminPartnerController::class, 'index']);
    Route::post('partners/{id}/verify', [AdminPartnerController::class, 'verify']);
    Route::get('deliveries', [AdminDeliveryController::class, 'index']);
});
