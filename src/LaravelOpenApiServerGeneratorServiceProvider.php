<?php

namespace Ensi\LaravelOpenApiServerGenerator;

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Data\Controllers\ControllersStorage;
use Ensi\LaravelOpenApiServerGenerator\Utils\PSR4PathConverter;
use Ensi\LaravelOpenApiServerGenerator\Utils\TemplatesManager;
use Illuminate\Support\ServiceProvider;

class LaravelOpenApiServerGeneratorServiceProvider extends ServiceProvider
{
    public const CONFIG_FILE_NAME = 'openapi-server-generator.php';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/' . self::CONFIG_FILE_NAME,
            'openapi-server-generator'
        );

        $this->app->when(TemplatesManager::class)
            ->needs('$fallbackPath')
            ->give(config('openapi-server-generator.extra_templates_path', ''));

        $this->app->when(PSR4PathConverter::class)
            ->needs('$mappings')
            ->give(config('openapi-server-generator.namespaces_to_directories_mapping', []));

        $this->app->singleton(ControllersStorage::class, function () {
            return new ControllersStorage();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/' . self::CONFIG_FILE_NAME => config_path(self::CONFIG_FILE_NAME),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateServer::class,
            ]);
        }
    }
}
