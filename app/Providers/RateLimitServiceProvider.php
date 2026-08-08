<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register rate limiters used by the API.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn () => Limit::perMinute(5)->by(request()->input('email').'|'.request()->ip()));
        RateLimiter::for('register', fn () => Limit::perMinute(3)->by(request()->ip()));
        RateLimiter::for('password', fn () => Limit::perMinute(5)->by(request()->input('email').'|'.request()->ip()));
    }
}
