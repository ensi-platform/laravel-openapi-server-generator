<?php

namespace Ensi\LaravelOpenApiServerGenerator\Tests;

use Ensi\LaravelOpenApiServerGenerator\LaravelOpenApiServerGeneratorServiceProvider;
use Ensi\LaravelOpenApiServerGenerator\Utils\ClassParser;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClassParserGenerator();
    }

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

    public function mockClassParserGenerator(): void
    {
        $parser = $this->mock(ClassParser::class);

        $parser->shouldReceive('parse')->andReturnSelf();
        $parser->shouldReceive('hasMethod')->andReturn(false);
        $parser->shouldReceive('getContentWithAdditionalMethods')->andReturnArg(0);
    }

    /**
     * Откатывает действие метода mockClassParserGenerator
     * @return void
     */
    protected function forgetMockClassParserGenerator(): void
    {
        $this->forgetMock(ClassParser::class);
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
