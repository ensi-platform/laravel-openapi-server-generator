<?php

namespace Greensight\LaravelOpenapiServerGenerator;

use Illuminate\Support\ServiceProvider;

use Greensight\LaravelOpenapiServerGenerator\Commands\GenerateServerVersion;
use Greensight\LaravelOpenapiServerGenerator\Commands\GenerateServer;

class OpenapiServerGeneratorServiceProvider extends ServiceProvider
{
    CONST CONFIG_FILE_NAME = 'openapi-server-generator.php';

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/' . self::CONFIG_FILE_NAME, self::CONFIG_FILE_NAME
        );
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/' . self::CONFIG_FILE_NAME => config_path(self::CONFIG_FILE_NAME)
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateServerVersion::class,
                GenerateServer::class
            ]);
        }
    }
}
