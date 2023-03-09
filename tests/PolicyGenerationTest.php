<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEqualsCanonicalizing;
use function PHPUnit\Framework\assertNotEqualsCanonicalizing;

test('Correct methods in generated policy', function () {
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

    $policies = [];
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$policies) {
        if (str_contains($path, 'Policy.php')) {
            $policies[pathinfo($path, PATHINFO_BASENAME)] = $content;
        }

        return true;
    });

    artisan(GenerateServer::class);

    foreach ($policies as $key => $content) {
        $methods = [];
        preg_match_all('~public function (.*)\(~', $content, $methods);
        $policies[$key] = $methods[1];
    }

    assertEqualsCanonicalizing(['methodFoo', 'methodBar'], $policies['PoliciesControllerPolicy.php']);
    assertNotEqualsCanonicalizing(['methodWithoutForbiddenResponse'], $policies['PoliciesControllerPolicy.php']);
});
