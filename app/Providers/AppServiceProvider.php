<?php

namespace App\Providers;

use App\Models\ApiKey;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            if (!config('app.api_throttle', true)) {
                return Limit::none();
            }

            // Default: 60 requests/minute per IP
            $clientIp = $request->getClientIp();

            return Limit::perMinute(60)->by($clientIp);
        });

        Route::model('token', ApiKey::class);

        Gate::define('viewPulse', function (?User $user): bool {
            return $user && $user->hasRole('admin');
        });

        // Customize the password reset URL globally, per Laravel documentation
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            // Build the absolute URL to the reset form with token as a path param and email as query
            $url = route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            // Log for diagnostics (web channel)
            try {
                Log::channel('web')->info('ResetPassword URL created', [
                    'email' => $user->getEmailForPasswordReset(),
                    'url' => $url,
                ]);
            } catch (\Throwable $e) {
                // Silently ignore logging issues
            }

            return $url;
        });
    }
}
