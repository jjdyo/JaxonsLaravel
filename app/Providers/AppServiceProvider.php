<?php

namespace App\Providers;

use App\Models\ApiKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        // Configure the 'api' rate limiter
        RateLimiter::for('api', function (Request $request) {
            // @phpstan-ignore-next-line
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Bind route model for API keys
        Route::model('token', ApiKey::class);
    }
}
