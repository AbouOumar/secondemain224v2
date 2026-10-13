<?php
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
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
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\EmailPreferenceController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\BoostController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\SearchAlertController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\RoleRequestController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        // Le 3e paramètre sépare les compteurs : sans lui, toutes ces routes partageraient la même limite.
        Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:5,1,register');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1,login');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1,forgot');
        Route::post('reset-password', [ForgotPasswordController::class, 'reset'])->middleware('throttle:5,1,reset');
        Route::post('oauth/google', [SocialAuthController::class, 'google'])->middleware('throttle:10,1,google');
        Route::post('oauth/facebook', [SocialAuthController::class, 'facebook']);
    });
    Route::get('pages/{slug}', [PageController::class, 'show']);
    Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1,contact');
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{slug}', [ArticleController::class, 'show']);
    Route::get('partners', [PartnerController::class, 'index']);
    Route::get('partners/{slug}', [PartnerController::class, 'show']);
    Route::get('sellers/{user}/stats', [SellerController::class, 'stats']);
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('auth/logout', [LogoutController::class, 'store']);
        Route::post('auth/email/verification-notification', [EmailVerificationController::class, 'store'])->middleware('throttle:3,1');
        Route::post('auth/logout-all', [LogoutController::class, 'destroyAll']);
        Route::middleware('role:revendeur_pro')->prefix('shop')->group(function () {
            Route::get('/', [ShopController::class, 'show']);
            Route::post('/', [ShopController::class, 'store']);
            Route::put('/', [ShopController::class, 'update']);
            Route::get('dashboard', [ShopController::class, 'dashboard']);
        });
        Route::get('role-request', [RoleRequestController::class, 'show']);
        Route::post('role-request', [RoleRequestController::class, 'store'])->middleware('throttle:5,1,role-request');
        Route::delete('role-request', [RoleRequestController::class, 'destroy']);
        Route::post('devices', [DeviceTokenController::class, 'store']);
        Route::delete('devices', [DeviceTokenController::class, 'destroy']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::delete('profile', [ProfileController::class, 'destroy']);
        Route::post('profile/avatar', [ProfileController::class, 'avatar']);
        Route::get('profile/saved-ids', [ProfileController::class, 'savedIds']);
        Route::get('profile/saved-articles', [ProfileController::class, 'saved']);
        Route::get('profile/email-preferences', [EmailPreferenceController::class, 'show']);
        Route::put('profile/email-preferences', [EmailPreferenceController::class, 'update']);
        Route::post('articles', [ArticleController::class, 'store']);
        Route::put('articles/{article}', [ArticleController::class, 'update']);
        Route::delete('articles/{article}', [ArticleController::class, 'destroy']);
        Route::delete('articles/{article}/images/{image}', [ArticleController::class, 'destroyImage']);
        Route::post('articles/{article}/boost', [BoostController::class, 'store']);
        Route::get('boost/pricing', [BoostController::class, 'pricing']);
        Route::post('articles/{article}/saved', [ArticleController::class, 'toggleSave']);
        Route::get('articles/{article}/saved-status', [ArticleController::class, 'checkSave']);
        Route::get('seller/articles', [ArticleController::class, 'myArticles']);
        Route::get('seller/stats', [ArticleController::class, 'stats']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{reference}', [OrderController::class, 'show']);
        Route::post('orders/{reference}/delivery', [OrderController::class, 'setDelivery']);
        Route::delete('orders/{reference}/delivery', [OrderController::class, 'removeDelivery']);
        Route::post('orders/{reference}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{reference}/confirm-receipt', [OrderController::class, 'confirmReceipt']);
        Route::get('seller/orders', [OrderController::class, 'sellerOrders']);
        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('payments/{reference}', [PaymentController::class, 'show']);
        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/topup', [WalletController::class, 'topup'])->middleware('throttle:10,1,topup');
        Route::get('wallet/withdrawals', [WalletController::class, 'withdrawals']);
        Route::post('wallet/withdrawals', [WalletController::class, 'requestWithdrawal'])->middleware('throttle:5,1,withdraw');
        Route::post('deliveries/position', [DeliveryController::class, 'position'])->middleware('throttle:30,1,position')->name('api.deliveries.position');
        Route::post('deliveries/position-token', [DeliveryController::class, 'createPositionToken'])->middleware('throttle:10,1,position-token');
        Route::delete('deliveries/position-token', [DeliveryController::class, 'deletePositionToken']);
        Route::get('deliveries/current', [DeliveryController::class, 'current']);
        Route::get('deliveries/earnings', [DeliveryController::class, 'earnings']);
        Route::get('deliveries/available', [DeliveryController::class, 'available']);
        Route::post('deliveries/{delivery}/accept', [DeliveryController::class, 'accept']);
        Route::post('deliveries/{delivery}/pickup', [DeliveryController::class, 'pickup']);
        Route::post('deliveries/{delivery}/complete', [DeliveryController::class, 'complete']);
        Route::post('deliveries/{delivery}/confirm-receipt', [DeliveryController::class, 'confirmReceipt']);
        Route::post('deliveries/{delivery}/tracking', [DeliveryController::class, 'tracking']);
        Route::put('deliveries/status', [DeliveryController::class, 'setStatus']);
        Route::get('deliveries/history', [DeliveryController::class, 'history']);
        Route::get('messages/conversations', [MessageController::class, 'conversations']);
        Route::get('messages/{user}', [MessageController::class, 'index']);
        Route::post('messages/{user}', [MessageController::class, 'store']);
        Route::put('messages/{message}/read', [MessageController::class, 'markRead']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('ratings', [RatingController::class, 'store']);
        Route::get('users/{user}/ratings', [RatingController::class, 'userRatings']);
        Route::post('reports', [ReportController::class, 'store']);

        Route::get('offers', [OfferController::class, 'index']);
        Route::post('articles/{article}/offers', [OfferController::class, 'store']);
        Route::post('offers/{offer}/accept', [OfferController::class, 'accept']);
        Route::post('offers/{offer}/reject', [OfferController::class, 'reject']);
        Route::post('offers/{offer}/counter', [OfferController::class, 'counter']);
        Route::post('offers/{offer}/cancel', [OfferController::class, 'cancel']);

        Route::get('search-alerts', [SearchAlertController::class, 'index']);
        Route::post('search-alerts', [SearchAlertController::class, 'store']);
        Route::post('search-alerts/{alert}/toggle', [SearchAlertController::class, 'toggle']);
        Route::delete('search-alerts/{alert}', [SearchAlertController::class, 'destroy']);

        Route::prefix('verification')->group(function () {
            Route::get('status', [App\Http\Controllers\Api\VerificationController::class, 'status'])->name('api.verification.status');
            Route::post('submit', [App\Http\Controllers\Api\VerificationController::class, 'store'])->name('api.verification.submit');
            Route::middleware('role:admin')->group(function () {
                Route::post('approve/{user}', [App\Http\Controllers\Api\VerificationController::class, 'approve'])->name('api.verification.approve');
                Route::post('reject/{user}', [App\Http\Controllers\Api\VerificationController::class, 'reject'])->name('api.verification.reject');
            });
        });

        Route::prefix('subscription')->middleware('role:revendeur_pro')->group(function () {
            Route::get('plans', [App\Http\Controllers\Api\SubscriptionController::class, 'plans'])->name('api.subscription.plans');
            Route::get('current', [App\Http\Controllers\Api\SubscriptionController::class, 'current'])->name('api.subscription.current');
            Route::post('store', [App\Http\Controllers\Api\SubscriptionController::class, 'store'])->name('api.subscription.store');
            Route::post('cancel', [App\Http\Controllers\Api\SubscriptionController::class, 'cancel'])->name('api.subscription.cancel');
            Route::get('history', [App\Http\Controllers\Api\SubscriptionController::class, 'history'])->name('api.subscription.history');
        });
    });

    Route::post('payments/djomy/webhook', [PaymentController::class, 'djomyWebhook']);
});
