<?php

declare(strict_types=1);

namespace Sburina\Whmcs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * WHMCS service provider.
 * Registers the Whmcs singleton, the whmcs auth driver, and publishes config.
 */
class WhmcsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $source = dirname(__DIR__) . '/config/whmcs.php';
        $this->publishes([$source => config_path('whmcs.php')]);
        $this->mergeConfigFrom($source, 'whmcs');

        Auth::provider('whmcs', function () {
            return new UserProvider();
        });
    }

    public function register(): void
    {
        $this->app->singleton('whmcs', function () {
            return new Whmcs();
        });
        $this->app->alias('whmcs', Whmcs::class);
    }

    /**
     * @return array<string>
     */
    public function provides(): array
    {
        return ['whmcs'];
    }
}
