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
    $resources = [];
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$resources) {
        if (str_contains($path, 'Resource.php')) {
            $resources[pathinfo($path, PATHINFO_BASENAME)] = $content;
        }

        return true;
    });


    artisan(GenerateServer::class);

    // С помощью регулярки достаем все выражения в кавычках
    foreach ($resources as $key => $content) {
        $matches = [];
        preg_match_all('~[\'](.*)[\']~', $content, $matches);
        $resources[$key] = $matches[1];
    }

    assertEqualsCanonicalizing(['foo', 'bar'], $resources['ResourcesResource.php']);
    assertEqualsCanonicalizing(['foo', 'bar'], $resources['ResourcesDataDataResource.php']);
    assertEqualsCanonicalizing(['foo', 'bar'], $resources['ResourcesDataWithNameResource.php']);
    assertEqualsCanonicalizing(['data'], $resources['ResourceRootResource.php']);
});
