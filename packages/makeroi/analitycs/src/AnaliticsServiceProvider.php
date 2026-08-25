<?php

namespace Makeroi\Analitics;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Routing\Router;
use Makeroi\Analitics\Http\Controllers\SpaController;
use Makeroi\Analitics\Http\Controllers\TableController;
use Makeroi\Analitics\Services\AnalyticsBootstrap;
use Makeroi\Analitics\Services\PanelRegistry;
use Makeroi\LaravelPackageTools\Package;
use Makeroi\LaravelPackageTools\PackageServiceProvider;

class AnaliticsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-analitycs')
            ->hasConfigFile('makeroi/analitycs');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PanelRegistry::class, function () {
            $registry = new PanelRegistry;
            $registry->assertValidPanels($registry->panelClasses());

            return $registry;
        });

        $this->app->singleton(AnalyticsBootstrap::class);
    }

    public function packageBooted(): void
    {
        if (! config('makeroi.analitycs.enabled', true)) {
            return;
        }

        $this->app->booted(function () {
            $this->registerPanelRoutes();
            $this->registerSpaRoutes();
        });
    }

    protected function registerPanelRoutes(): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $registry = $this->app->make(PanelRegistry::class);

        foreach ($registry->all() as $slug => $panel) {
            if (! $panel->enabled()) {
                continue;
            }

            $this->app->make(Router::class)
                ->middleware($panel->middleware())
                ->prefix($panel->routePrefix())
                ->get($panel->routeUri(), TableController::class)
                ->defaults('analytics_panel', $slug)
                ->name($panel->routeName());
        }
    }

    protected function registerSpaRoutes(): void
    {
        if (! config('makeroi.analitycs.spa.enabled', true)) {
            return;
        }

        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $spa = config('makeroi.analitycs.spa', []);
        $prefix = $spa['route_prefix'] ?? 'analytics';
        $middleware = $spa['middleware'] ?? ['web'];
        $name = $spa['route_name'] ?? 'makeroi.analitycs.spa';

        $this->app->make(Router::class)
            ->middleware($middleware)
            ->prefix($prefix)
            ->get('/{path?}', SpaController::class)
            ->where('path', '.*')
            ->name($name);
    }
}
