<?php

namespace App\Providers;

use App\Auth\JwtGuard;
use App\Services\JwtService;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class);
    }

    public function boot(): void
    {
        Gate::define('viewApiDocs', static fn (): bool => (bool) config('app.api_docs_public'));
        Scramble::configure()->expose(
            ui: static fn (Router $router, $action) => $router->get('api/docs', $action)->name('scramble.docs.ui'),
            document: static fn (Router $router, $action) => $router->get('api/docs.json', $action)->name('scramble.docs.document'),
        );

        Auth::extend('jwt', function ($app, string $name, array $config) {
            return new JwtGuard(
                $app->make(JwtService::class),
                Auth::createUserProvider($config['provider']),
                $app['request'],
            );
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('refresh', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
