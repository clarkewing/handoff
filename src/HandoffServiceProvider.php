<?php

namespace ClarkeWing\Handoff;

use ClarkeWing\Handoff\Http\Middleware\RedirectGuestToHandoffLogin;
use ClarkeWing\Handoff\Http\Middleware\RedirectToHandoff;
use ClarkeWing\Handoff\Http\Middleware\RememberHandoffIntended;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class HandoffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerPackageConfig();
    }

    public function boot(): void
    {
        $this->configurePackageRateLimiting();

        $this->registerPackageRoutes();

        $this->registerPackageMiddleware();

        $this->publishPackageConfig();
    }

    protected function configurePackageRateLimiting(): void
    {
        RateLimiter::for('handoff', function (Request $request) {
            return Limit::perMinute(10);
        });
    }

    protected function registerPackageRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/handoff.php');
    }

    protected function registerPackageMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('handoff', RedirectToHandoff::class);
        $router->aliasMiddleware('handoff.login', RedirectGuestToHandoffLogin::class);
        $router->aliasMiddleware('handoff.intended', RememberHandoffIntended::class);
    }

    protected function registerPackageConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/handoff.php', 'handoff'
        );
    }

    protected function publishPackageConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/handoff.php' => config_path('handoff.php'),
        ], 'handoff-config');
    }
}
