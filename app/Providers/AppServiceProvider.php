<?php

namespace App\Providers;

use App\Models\ApiKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
        RateLimiter::for('api', function (Request $request) {
            // If API_THROTTLE=false, disable rate limiting entirely
            if (! config('app.api_throttle', true)) {
                return Limit::none();
            }

            // Default: 60 requests/minute per user or IP
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        Route::model('token', ApiKey::class);

        Gate::define('viewPulse', function (?User $user): bool {
            return $user && $user->hasRole('admin');
        });
    }

