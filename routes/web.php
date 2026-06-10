<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ArticleController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/listings', [ProfileController::class, 'listings'])->name('listings');
        Route::get('/saved', [ProfileController::class, 'saved'])->name('saved');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::post('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [ProfileController::class, 'avatar'])->name('avatar');
    });

    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/create', [ArticleController::class, 'create'])->name('create');
        Route::post('/', [ArticleController::class, 'store'])->name('store');
        Route::get('/{article}/edit', [ArticleController::class, 'edit'])->name('edit');
        Route::put('/{article}', [ArticleController::class, 'update'])->name('update');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('destroy');
        Route::post('/{article}/toggle-status', [ArticleController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Motard routes
    Route::prefix('motard')->name('motard.')->middleware('role:motard')->group(function () {
        Route::get('/tableau-de-bord', [\App\Http\Controllers\Web\MotardController::class, 'dashboard'])->name('dashboard');
    });

    // Delivery routes for motard
    Route::prefix('deliveries')->name('deliveries.')->middleware('role:motard')->group(function () {
        Route::post('/{id}/accept', [\App\Http\Controllers\Web\MotardController::class, 'accept'])->name('accept');
        Route::post('/{id}/pickup', [\App\Http\Controllers\Web\MotardController::class, 'pickup'])->name('pickup');
        Route::post('/{id}/complete', [\App\Http\Controllers\Web\MotardController::class, 'complete'])->name('complete');
        Route::post('/set-status', [\App\Http\Controllers\Web\MotardController::class, 'setStatus'])->name('setStatus');
    });

    // Seller Pro routes
    Route::prefix('seller/pro')->name('seller.pro.')->middleware(['auth', 'role:revendeur_pro'])->group(function () {
        Route::get('/tableau-de-bord', [\App\Http\Controllers\Web\ProfileController::class, 'proDashboard'])->name('dashboard');
        Route::get('/abonnement', [\App\Http\Controllers\Web\ProfileController::class, 'subscription'])->name('subscription');
        Route::get('/verification', [\App\Http\Controllers\Web\ProfileController::class, 'verification'])->name('verification');
    });

    // Magasin routes (seller pro)
    Route::middleware(['auth', 'role:revendeur_pro'])->prefix('seller/pro/magasin')->name('magasin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\MagasinController::class, 'dashboard'])->name('dashboard');
        Route::get('/creer', [\App\Http\Controllers\Web\MagasinController::class, 'setup'])->name('setup');
        Route::post('/creer', [\App\Http\Controllers\Web\MagasinController::class, 'store'])->name('store');
        Route::get('/modifier', [\App\Http\Controllers\Web\MagasinController::class, 'edit'])->name('edit');
        Route::post('/modifier', [\App\Http\Controllers\Web\MagasinController::class, 'update'])->name('update');
    });
});

Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/{slug}', [ArticleController::class, 'show'])->name('show');
});

Route::get('/boutique/{slug}', [\App\Http\Controllers\Web\MagasinController::class, 'show'])->name('magasin.show');

Route::get('/nous', [App\Http\Controllers\Web\NousController::class, 'index'])->name('nous');
Route::get('/contact', [App\Http\Controllers\Web\ContactController::class, 'index'])->name('contact');
Route::post('/contact', [App\Http\Controllers\Web\ContactController::class, 'send'])->name('contact.send');

Route::get('/revendre', function () {
    return redirect()->route('articles.create');
})->name('revendre');

Route::middleware('auth')->group(function () {
    Route::post('/orders/create/{article}/{delivery}', [\App\Http\Controllers\Web\OrderController::class, 'create'])->name('orders.create');

    Route::prefix('paiement')->name('payment.')->group(function () {
        Route::get('/{order}', [\App\Http\Controllers\Web\PaymentController::class, 'show'])->name('show');
        Route::post('/{order}/djomy', [\App\Http\Controllers\Web\PaymentController::class, 'processDjomy'])->name('process.djomy');
        Route::match(['GET', 'POST'], '/{order}/callback', [\App\Http\Controllers\Web\PaymentController::class, 'callback'])->name('callback');
    });

    Route::match(['GET', 'POST'], '/saved/{article}', [\App\Http\Controllers\Web\ProfileController::class, 'toggleSave'])->name('saved.toggle');
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\MessageController::class, 'index'])->name('index');
        Route::get('/{user}/{article?}', [\App\Http\Controllers\Web\MessageController::class, 'show'])->name('show');
        Route::post('/{receiver}', [\App\Http\Controllers\Web\MessageController::class, 'store'])->name('store');
    });
    Route::post('/articles/{article}/boost', [\App\Http\Controllers\Web\ArticleController::class, 'boost'])->name('articles.boost');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [\App\Http\Controllers\Web\NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [\App\Http\Controllers\Web\NotificationController::class, 'markAllRead'])->name('readAll');
    });

    // Motard live tracking
    Route::middleware('role:motard')->group(function () {
        Route::get('/motard/livraison/{id}/tracking', [\App\Http\Controllers\Web\TrackingController::class, 'motardTracking'])->name('motard.tracking');
        Route::post('/motard/livraison/{id}/position', [\App\Http\Controllers\Web\TrackingController::class, 'updatePosition'])->name('motard.position');
    });

    // Public tracking (buyers, sellers, admins)
    Route::get('/livraison/{id}/suivi', [\App\Http\Controllers\Web\TrackingController::class, 'publicTracking'])->name('deliveries.tracking');
    Route::get('/livraison/{id}/track', [\App\Http\Controllers\Web\TrackingController::class, 'getTrack'])->name('deliveries.track');
});
