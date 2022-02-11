<?php

namespace Ensi\LaravelOpenApiServerGenerator\Tests;

use Ensi\LaravelOpenApiServerGenerator\LaravelOpenApiServerGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelOpenApiServerGeneratorServiceProvider::class,
        ];
    }

    public function makeFilePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
