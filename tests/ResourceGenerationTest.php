<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEqualsCanonicalizing;

test('Test allOff and ref keywords', function () {
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
    $resource = null;
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$resource) {
        if (str_contains($path, 'ResourcesResource.php')) {
            $resource = $content;
        }

        return true;
    });
    $propertiesInResource = ['foo', 'bar'];

    artisan(GenerateServer::class);

    // С помощью регулярки достаем все выражения в кавычках
    preg_match_all('~[\'](.*)[\']~', $resource, $matches);

    assertEqualsCanonicalizing($propertiesInResource, $matches[1]);
});
