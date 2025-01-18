<?php

namespace App\Providers;

use App\Services\ChatService;
use App\Services\CustomInstructionsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CustomInstructionsService::class, function ($app) {
            return new CustomInstructionsService();
        });

        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService($app->make(CustomInstructionsService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
