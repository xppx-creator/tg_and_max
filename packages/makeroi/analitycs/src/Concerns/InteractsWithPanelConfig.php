<?php

namespace Makeroi\Analitics\Concerns;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use packages\makeroi\analitycs\src\Contracts\TablePanelConfigBuilder;
use RuntimeException;

trait InteractsWithPanelConfig
{
    /**
     * @return array<string, mixed>
     */
    protected function configFromFile(string $path): array
    {
        return $this->loadJsonFile($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function configFromSource(string $path): array
    {
        if (! app()->bound(TablePanelConfigBuilder::class)) {
            throw new RuntimeException(
                'Panel source is configured but no TablePanelConfigBuilder is bound. '
                .'Register Makeroi\\Analitics\\Contracts\\TablePanelConfigBuilder or use configFromFile().'
            );
        }

        return app(TablePanelConfigBuilder::class)->build($this->loadJsonFile($path));
    }

    protected function packageConfigStubPath(): string
    {
        return dirname(__DIR__, 2).'/resources/stubs/table.conf.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function loadJsonFile(string $path): array
    {
        $files = app(Filesystem::class);

        if (! $files->isFile($path)) {
            throw new RuntimeException("Analytics JSON file not found: {$path}");
        }

        try {
            $contents = $files->get($path);
        } catch (FileNotFoundException $e) {
            throw new RuntimeException("Analytics JSON file not readable: {$path}", 0, $e);
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Analytics JSON must be an object: {$path}");
        }

        return $decoded;
    }
}
