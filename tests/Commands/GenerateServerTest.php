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
        $app['config']->set('openapi-server-generator.apidoc_dir', './tests/api-docs');
        $app['config']->set('openapi-server-generator.temp_dir', './generated');
        $app['config']->set('openapi-server-generator.destination_dir', 'OpenApiGenerated');
    }

    protected function getPackageProviders($app)
    {
        return [
            'Greensight\LaravelOpenapiServerGenerator\OpenapiServerGeneratorServiceProvider'
        ];
    }

    public function testPushAndPop()
    {
        $code = $this->artisan('openapi:generate-server');
        $this->assertSame($code, 0);
    }
}
