<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertStringEqualsFile;

test('Check valid creating Laravel Validation Rules in Request with application/json content type', function () {
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
    $request = null;
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$request) {
        if (str_contains($path, 'LaravelValidationsApplicationJsonRequest.php')) {
            $request = $content;
        }

        return true;
    });

    artisan(GenerateServer::class);

    $validationsStart = strpos($request, "public function rules(): array") + 37;
    $validationsEnd = strpos($request, '];', $validationsStart) + 2;
    $validations = substr($request, $validationsStart, $validationsEnd - $validationsStart);

    assertStringEqualsFile(__DIR__ . '/expects/LaravelValidationsApplicationJsonRequest.php', $validations);
});

test('Check valid creating Laravel Validation Rules in Request with multipart/form-data content type', function () {
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
    $request = null;
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$request) {
        if (str_contains($path, 'LaravelValidationsMultipartFormDataRequest.php')) {
            $request = $content;
        }

        return true;
    });

    artisan(GenerateServer::class);

    $validationsStart = strpos($request, "public function rules(): array") + 37;
    $validationsEnd = strpos($request, '];', $validationsStart) + 2;
    $validations = substr($request, $validationsStart, $validationsEnd - $validationsStart);

    assertStringEqualsFile(__DIR__ . '/expects/LaravelValidationsMultipartFormDataRequest.php', $validations);
});

test('Check valid creating Laravel Validation Rules in Request with non available content type', function () {
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
    $request = null;
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$request) {
        if (str_contains($path, 'LaravelValidationsNonAvailableContentTypeRequest.php')) {
            $request = $content;
        }

        return true;
    });

    artisan(GenerateServer::class);

    $validationsStart = strpos($request, "public function rules(): array") + 37;
    $validationsEnd = strpos($request, '];', $validationsStart) + 2;
    $validations = substr($request, $validationsStart, $validationsEnd - $validationsStart);
    // For test on Windows replace \r\n to \n
    $validations = str_replace($validations, "\r\n", "\n");

    assertStringEqualsFile(__DIR__ . '/expects/LaravelValidationsNonAvailableContentTypeRequest.php', $validations);
});
