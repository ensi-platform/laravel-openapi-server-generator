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
        $app['config']->set('openapi-server-generator.apidoc_dir', ('./public/api-docs'));
        $app['config']->set('openapi-server-generator.output_dir', './generated');
        $app['config']->set('openapi-server-generator.app_dir', 'OpenApiGenerated');
        $app['config']->set(
            'openapi-server-generator.openapi_generator_bin',
            './node_modules/.bin/openapi-generator'
        );
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
