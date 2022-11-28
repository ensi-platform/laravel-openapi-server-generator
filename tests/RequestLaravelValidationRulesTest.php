<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Data\OpenApi3\OpenApi3Schema;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;

//it('can convert openapi specifications to objects', function () {
//    /** @var TestCase $this */
//    $mapping = Config::get('openapi-server-generator.api_docs_mappings');
//    $mappingValue = current($mapping);
//    $mapping = [$this->makeFilePath(__DIR__ . '/resources/index.yaml') => $mappingValue];
//    Config::set('openapi-server-generator.api_docs_mappings', $mapping);
//
//    $filesystem = $this->mock(Filesystem::class);
//    $filesystem->shouldReceive('put');
//
//    $schemaClass = $this->mock(OpenApi3Schema::class);
//    $resourceController = null;
//    $schemaClass->shouldReceive('fillFromStdRequestBody')
//        ->withArgs(function ($contentType, $routeRequestBody) use (&$resourceController, &$withoutResponsesController) {
//            dump($contentType);
//
//            return true;
//        });
//
//    artisan(GenerateServer::class);
//});
