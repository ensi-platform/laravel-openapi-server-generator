<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEqualsCanonicalizing;

test("Command GenerateServer success", function () {
    /** @var TestCase $this */
    $mapping = Config::get('openapi-server-generator.api_docs_mappings');
    $mappingValue = current($mapping);
    $mapping = [$this->makeFilePath(__DIR__ . '/resources/index.yaml') => $mappingValue];
    Config::set('openapi-server-generator.api_docs_mappings', $mapping);

    $filesystem = $this->mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(false);
    $filesystem->shouldReceive('get')->withArgs(function ($path) {
        return (bool)strstr($path, '.template');
    })->andReturnUsing(function ($path) {
        return file_get_contents($path);
    });
    $filesystem->shouldReceive('cleanDirectory', 'ensureDirectoryExists');
    $appRoot = realpath($this->makeFilePath(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/'));
    $putFiles = [];
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$putFiles, $appRoot) {
        $putFiles[] = $this->makeFilePath(str_replace($appRoot, '', $path));

        return true;
    });

    artisan(GenerateServer::class);

    assertEqualsCanonicalizing([
        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/routes.php'),

        $this->makeFilePath('/app/Http/Controllers/ResourcesController.php'),
        $this->makeFilePath('/app/Http/Requests/TestFullGenerateRequest.php'),
        $this->makeFilePath('/app/Http/Tests/ResourcesComponentTest.php'),
        $this->makeFilePath('/app/Http/Requests/TestFooRenameRequest.php'),

        $this->makeFilePath('/app/Http/Controllers/WithoutResponsesController.php'),

        $this->makeFilePath('/WithoutNamespaceController.php'),
        $this->makeFilePath('/WithoutNamespaceRequest.php'),
        $this->makeFilePath('/WithoutNamespaceComponentTest.php'),

        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestIntegerEnum.php'),
        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestStringEnum.php'),
    ], $putFiles);
});
