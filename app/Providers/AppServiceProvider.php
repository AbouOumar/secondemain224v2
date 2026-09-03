<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // The Blade front-end uses Bootstrap, not Tailwind.
        Paginator::useBootstrapFive();

        Gate::define('admin', function (User $user): bool {
            return $user->role->value === 'admin';
        });

        // Précharge une seule fois par requête les IDs des annonces enregistrées
        // par l'utilisateur connecté, pour afficher le bouton cœur sur toutes
        // les grilles d'annonces sans requête N+1 par carte.
        View::composer(
            ['partials.articles-grid', 'home', 'magasin.show', 'profile.saved', 'seller.public-profile', 'articles.show'],
            function ($view) {
                $savedIds = Auth::check()
                    ? Auth::user()->savedArticles()->pluck('articles.id')->all()
                    : [];
                $view->with('savedIds', $savedIds);
            }
        );
    }
}
