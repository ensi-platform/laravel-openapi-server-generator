<?php

use Orchestra\Testbench\TestCase;

class GenerateServerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutMockingConsoleOutput();
    }

    protected function getEnvironmentSetUp($app): void {
        $app['config']->set('openapi-server-generator.output_dir', './gen-test');
        $app['config']->set('openapi-server-generator.app_dir', './app-test/OpenApiGenerated');
    }

    protected function getPackageProviders($app)
    {
        return [
            'Greensight\LaravelOpenapiServerGenerator\OpenapiServerGeneratorServiceProvider'
        ];
    }

    public function testPushAndPop()
    {
        $this->artisan('openapi:generate-server')->execute();
    }
}