<?php

namespace App\Providers;

use App\Services\Marine\Contracts\WeatherProviderInterface;
use App\Services\Marine\OpenMeteoMarineService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WeatherProviderInterface::class, OpenMeteoMarineService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        RateLimiter::for('marine', fn ($request) => [
            Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
            Limit::perHour(300)->by($request->user()?->id ?: $request->ip()),
        ]);
        RateLimiter::for('route-preview', fn ($request) => [
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
            Limit::perHour(30)->by($request->user()?->id ?: $request->ip()),
        ]);
        RateLimiter::for('map-data', fn ($request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('reviews', fn ($request) => Limit::perHour(10)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('admin-sensitive', fn ($request) => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('csp-reports', fn ($request) => Limit::perMinute(30)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
