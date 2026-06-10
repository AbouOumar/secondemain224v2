<?php
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\BoostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [RegisterController::class, 'store']);
        Route::post('login', [LoginController::class, 'store']);
        Route::post('forgot-password', [ForgotPasswordController::class, 'store']);
        Route::post('reset-password', [ForgotPasswordController::class, 'reset']);
        Route::post('oauth/google', [SocialAuthController::class, 'google']);
        Route::post('oauth/facebook', [SocialAuthController::class, 'facebook']);
    });
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{slug}', [ArticleController::class, 'show']);
    Route::get('partners', [PartnerController::class, 'index']);
    Route::get('partners/{slug}', [PartnerController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile/avatar', [ProfileController::class, 'avatar']);
        Route::get('profile/saved-articles', [ProfileController::class, 'saved']);
        Route::post('articles', [ArticleController::class, 'store']);
        Route::put('articles/{id}', [ArticleController::class, 'update']);
        Route::delete('articles/{id}', [ArticleController::class, 'destroy']);
        Route::post('articles/{id}/boost', [BoostController::class, 'store']);
        Route::get('boost/pricing', [BoostController::class, 'pricing']);
        Route::post('articles/{id}/saved', [ArticleController::class, 'toggleSave']);
        Route::get('articles/{id}/saved-status', [ArticleController::class, 'checkSave']);
        Route::get('seller/articles', [ArticleController::class, 'myArticles']);
        Route::get('seller/stats', [ArticleController::class, 'stats']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{reference}', [OrderController::class, 'show']);
        Route::post('orders/{reference}/cancel', [OrderController::class, 'cancel']);
        Route::get('seller/orders', [OrderController::class, 'sellerOrders']);
        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('payments/{reference}', [PaymentController::class, 'show']);
        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::get('deliveries/available', [DeliveryController::class, 'available']);
        Route::post('deliveries/{id}/accept', [DeliveryController::class, 'accept']);
        Route::post('deliveries/{id}/pickup', [DeliveryController::class, 'pickup']);
        Route::post('deliveries/{id}/complete', [DeliveryController::class, 'complete']);
        Route::post('deliveries/{id}/tracking', [DeliveryController::class, 'tracking']);
        Route::put('deliveries/status', [DeliveryController::class, 'setStatus']);
        Route::get('deliveries/history', [DeliveryController::class, 'history']);
        Route::get('messages/conversations', [MessageController::class, 'conversations']);
        Route::get('messages/{user}', [MessageController::class, 'index']);
        Route::post('messages/{user}', [MessageController::class, 'store']);
        Route::put('messages/{id}/read', [MessageController::class, 'markRead']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('ratings', [RatingController::class, 'store']);
        Route::get('users/{id}/ratings', [RatingController::class, 'userRatings']);

        Route::prefix('verification')->group(function () {
            Route::get('status', [App\Http\Controllers\Api\VerificationController::class, 'status'])->name('api.verification.status');
            Route::post('submit', [App\Http\Controllers\Api\VerificationController::class, 'store'])->name('api.verification.submit');
            Route::middleware('role:admin')->group(function () {
                Route::post('approve/{user}', [App\Http\Controllers\Api\VerificationController::class, 'approve'])->name('api.verification.approve');
                Route::post('reject/{user}', [App\Http\Controllers\Api\VerificationController::class, 'reject'])->name('api.verification.reject');
            });
        });

        Route::prefix('subscription')->group(function () {
            Route::get('current', [App\Http\Controllers\Api\SubscriptionController::class, 'current'])->name('api.subscription.current');
            Route::post('store', [App\Http\Controllers\Api\SubscriptionController::class, 'store'])->name('api.subscription.store');
            Route::post('cancel', [App\Http\Controllers\Api\SubscriptionController::class, 'cancel'])->name('api.subscription.cancel');
            Route::get('history', [App\Http\Controllers\Api\SubscriptionController::class, 'history'])->name('api.subscription.history');
        });
    });

    Route::post('payments/webhook/orange-money', [PaymentController::class, 'omWebhook']);
    Route::post('payments/webhook/mtn-momo', [PaymentController::class, 'momoWebhook']);
    Route::post('payments/djomy/webhook', [PaymentController::class, 'djomyWebhook']);
});
