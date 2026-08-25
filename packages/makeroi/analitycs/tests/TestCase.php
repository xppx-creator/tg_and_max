<?php

namespace Makeroi\Analitics\Tests;

use packages\makeroi\analitycs\src\AnaliticsServiceProvider;
use Makeroi\Analitics\Tests\Support\ModelStubAnalyticsPanel;
use Makeroi\Analitics\Tests\Support\StubAnalyticsPanel;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AnaliticsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('makeroi.analitycs.enabled', true);
        $app['config']->set('makeroi.analitycs.panels', [
            StubAnalyticsPanel::class,
            ModelStubAnalyticsPanel::class,
        ]);
        $app['config']->set('makeroi.analitycs.route.prefix', 'api/v1');
        $app['config']->set('makeroi.analitycs.route.middleware', ['api']);
        $app['config']->set('makeroi.analitycs.spa.enabled', true);
        $app['config']->set('makeroi.analitycs.spa.route_prefix', 'analytics');
        $app['config']->set('makeroi.analitycs.spa.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('app.key', 'base64:2fl+Ktvk6xSg1cGIBQjLgq6nMDqQaGqjGRIvPmNKuRU=');
    }
}
