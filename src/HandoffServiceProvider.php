<?php

namespace ClarkeWing\Handoff;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Stringable;

class HandoffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerPackageConfig();
    }

    public function boot(): void
    {
        $this->configurePackageRateLimiting();

        $this->bootPackageRoutes();

        $this->publishPackageConfig();

        $this->bootPackageMacros();
    }

    protected function configurePackageRateLimiting(): void
    {
        RateLimiter::for('handoff', function (Request $request) {
            return Limit::perMinute(10);
        });
    }

    protected function bootPackageRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/handoff.php');
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

    protected function bootPackageMacros(): void
    {
        if ($this->laravelVersion() < 9) {
            Request::macro('string', function (?string $key, mixed $default = null): Stringable {
                return Str::of($this->input($key, $default));
            });
        }
    }

    protected function laravelVersion(): int
    {
        return (int) Str::before(app()->version(), '.');
    }
}
