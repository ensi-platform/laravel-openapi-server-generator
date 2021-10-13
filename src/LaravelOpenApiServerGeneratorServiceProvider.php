<?php

namespace Greensight\LaravelOpenApiServerGenerator;

use Greensight\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Greensight\LaravelOpenApiServerGenerator\Utils\PSR4PathConverter;
use Greensight\LaravelOpenApiServerGenerator\Utils\TemplatesManager;
use Illuminate\Support\ServiceProvider;

class LaravelOpenApiServerGeneratorServiceProvider extends ServiceProvider
{
    const CONFIG_FILE_NAME = 'openapi-server-generator.php';

    /**
     * @return void
     */
    public function register()
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
    }

    /**
     * @return void
     */
    public function boot()
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
