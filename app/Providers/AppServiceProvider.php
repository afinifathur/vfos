<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Finance\Identity\AccountIdentifierRepositoryInterface::class,
            \App\Repositories\Finance\Identity\EloquentAccountIdentifierRepository::class
        );

        $this->app->bind(
            \App\Services\Finance\Identity\AccountIdentityResolverInterface::class,
            \App\Services\Finance\Identity\AccountIdentityResolver::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
