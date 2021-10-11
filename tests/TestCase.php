<?php

namespace Greensight\LaravelOpenApiServerGenerator\Tests;

use Greensight\LaravelOpenApiServerGenerator\LaravelOpenApiServerGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelOpenApiServerGeneratorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
