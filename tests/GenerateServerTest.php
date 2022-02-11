<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEquals;

function makeFilePath(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}
test("Command GenerateServer success", function () {
    /** @var TestCase $this */
    $mapping = Config::get('openapi-server-generator.api_docs_mappings');
    $mappingValue = current($mapping);
    $mapping = [makeFilePath(__DIR__ . '/resources/index.yaml') => $mappingValue];
    Config::set('openapi-server-generator.api_docs_mappings', $mapping);

    $filesystem = $this->mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturn(false);
    $filesystem->shouldReceive('get')->withArgs(function ($path) {
        return (bool)strstr($path, '.template');
    })->andReturnUsing(function ($path) {
        return file_get_contents($path);
    });
    $filesystem->shouldReceive('cleanDirectory', 'ensureDirectoryExists');
    $appRoot = realpath(makeFilePath(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/'));
    $putFiles = [];
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$putFiles, $appRoot) {
        $putFiles[] = str_replace($appRoot, '', $path);

        return true;
    });

    artisan(GenerateServer::class);

    $needFiles = [
        makeFilePath('/app/Http/ApiV1/OpenApiGenerated/routes.php'),

        makeFilePath('/app/Http/Controllers/ResourcesController.php'),
        makeFilePath('/app/Http/Requests/TestFullGenerateRequest.php'),
        makeFilePath('/app/Http/Tests/ResourcesComponentTest.php'),
        makeFilePath('/app/Http/Requests/TestFooRenameRequest.php'),

        makeFilePath('/app/Http/Controllers/WithoutResponsesController.php'),

        makeFilePath('/WithoutNamespaceController.php'),
        makeFilePath('/WithoutNamespaceRequest.php'),
        makeFilePath('/WithoutNamespaceComponentTest.php'),

        makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestIntegerEnum.php'),
        makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestStringEnum.php'),
    ];
    sort($needFiles);
    sort($putFiles);
    assertEquals($needFiles, $putFiles);
});
