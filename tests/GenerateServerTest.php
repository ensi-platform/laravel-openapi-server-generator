<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertEqualsCanonicalizing;
use function PHPUnit\Framework\assertNotTrue;
use function PHPUnit\Framework\assertStringContainsString;

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
        $filePath = $this->makeFilePath(str_replace($appRoot, '', $path));
        $putFiles[$filePath] = $filePath;

        return true;
    });

    artisan(GenerateServer::class);

    assertEqualsCanonicalizing([
        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/routes.php'),

        $this->makeFilePath('/app/Http/Controllers/Foo/TestController.php'),
        $this->makeFilePath('/app/Http/Controllers/ResourcesController.php'),
        $this->makeFilePath('/app/Http/Requests/TestFullGenerateRequest.php'),
        $this->makeFilePath('/app/Http/Tests/ResourcesComponentTest.php'),
        $this->makeFilePath('/app/Http/Requests/TestFooRenameRequest.php'),

        $this->makeFilePath('/app/Http/Requests/WithDirRequests/Request.php'),
        $this->makeFilePath('/app/Http/Requests/Foo/TestNamespaceWithDirRequest.php'),
        $this->makeFilePath('/app/Http/Requests/LaravelValidationsApplicationJsonRequest.php'),
        $this->makeFilePath('/app/Http/Requests/LaravelValidationsMultipartFormDataRequest.php'),
        $this->makeFilePath('/app/Http/Requests/LaravelValidationsNonAvailableContentTypeRequest.php'),

        $this->makeFilePath('/app/Http/Controllers/WithoutResponsesController.php'),

        $this->makeFilePath('/WithoutNamespaceController.php'),
        $this->makeFilePath('/WithoutNamespaceRequest.php'),
        $this->makeFilePath('/WithoutNamespaceComponentTest.php'),

        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestIntegerEnum.php'),
        $this->makeFilePath('/app/Http/ApiV1/OpenApiGenerated/Enums/TestStringEnum.php'),

        $this->makeFilePath('/app/Http/Resources/ResourcesResource.php'),
        $this->makeFilePath('/app/Http/Resources/ResourcesDataDataResource.php'),
        $this->makeFilePath('/app/Http/Resources/Foo/ResourcesDataDataResource.php'),
        $this->makeFilePath('/app/Http/Resources/ResourceRootResource.php'),
        $this->makeFilePath('/app/Http/Resources/Foo/WithDirResource.php'),
        $this->makeFilePath('/app/Http/Tests/Foo/TestComponentTest.php'),

        $this->makeFilePath('/app/Http/Requests/TestRenameFromKeyRequestRequest.php'),
        $this->makeFilePath('/app/Http/Resources/ResourcesDataWithNameResource.php'),

        $this->makeFilePath('/app/Http/Controllers/Controller11.php'),
        $this->makeFilePath('/app/Http/Controllers/Controller2.php'),
        $this->makeFilePath('/app/Http/Controllers/FooItemsController.php'),
        $this->makeFilePath('/app/Http/Controllers/FoosController.php'),
        $this->makeFilePath('/app/Http/Controllers/PoliciesController.php'),
        $this->makeFilePath('/app/Http/Tests/PoliciesComponentTest.php'),
        $this->makeFilePath('/app/Http/Policies/PoliciesControllerPolicy.php'),
    ], array_values($putFiles));
});

test("Correct requests in controller methods", function () {
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
    $resourceController = null;
    $withoutResponsesController = null;
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$resourceController, &$withoutResponsesController) {
        if (str_contains($path, 'ResourcesController.php')) {
            $resourceController = $content;
        }

        if (str_contains($path, 'WithoutResponsesController.php')) {
            $withoutResponsesController = $content;
        }

        return true;
    });

    artisan(GenerateServer::class);

    assertNotTrue(is_null($resourceController), 'ResourceController exist');
    assertStringContainsString(
        'use App\Http\Requests\TestFooRenameRequest',
        $resourceController,
        'ResourceController import'
    );
    assertStringContainsString(
        'TestFullGenerateRequest $request',
        $resourceController,
        'ResourceController function parameter'
    );

    assertNotTrue(is_null($withoutResponsesController), 'WithoutResponsesController exist');
    assertStringContainsString(
        'use Illuminate\Http\Request',
        $withoutResponsesController,
        'WithoutResponsesController import'
    );
    assertStringContainsString(
        'Request $request',
        $withoutResponsesController,
        'WithoutResponsesController function parameter'
    );
});


test('namespace sorting', function () {
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
    $routes = '';
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$routes, &$rr) {
        if (str_contains($path, 'routes.php')) {
            $routes = $content;
        }

        return true;
    });

    artisan(GenerateServer::class, ['-e' => 'routes']);

    assertStringContainsString(
        "use App\Http\Controllers\Controller11;\n".
        "use App\Http\Controllers\Controller2;\n".
        "use App\Http\Controllers\Foo\TestController;\n" .
        "use App\Http\Controllers\FooItemsController;\n".
        "use App\Http\Controllers\FoosController;\n",
        $routes
    );
});

test("Update tests success", function (array $parameters, bool $withControllerEntity) {
    /** @var TestCase $this */
    $mapping = Config::get('openapi-server-generator.api_docs_mappings');
    $mappingValue = current($mapping);
    $mapping = [$this->makeFilePath(__DIR__ . '/resources/index.yaml') => $mappingValue];
    Config::set('openapi-server-generator.api_docs_mappings', $mapping);

    $appRoot = realpath($this->makeFilePath(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/'));

    $existTest = $this->makeFilePath('/app/Http/Tests/ResourcesComponentTest.php');

    $filesystem = $this->mock(Filesystem::class);
    $filesystem->shouldReceive('exists')->andReturnUsing(function ($path) use ($appRoot, $existTest) {
        $filePath = $this->makeFilePath(str_replace($appRoot, '', $path));

        return $filePath === $existTest;
    });

    $filesystem->shouldReceive('get')->withArgs(function ($path) {
        return (bool)strstr($path, '.template');
    })->andReturnUsing(function ($path) {
        return file_get_contents($path);
    });
    $filesystem->shouldReceive('cleanDirectory', 'ensureDirectoryExists');

    $putFiles = [];
    $filesystem->shouldReceive('put')->withArgs(function ($path, $content) use (&$putFiles, $appRoot) {
        $filePath = $this->makeFilePath(str_replace($appRoot, '', $path));
        $putFiles[$filePath] = $filePath;

        return true;
    });

    $appendFiles = [];
    $filesystem->shouldReceive('append')->withArgs(function ($filePath, $data) use (&$appendFiles, $appRoot, $existTest) {
        $filePath = $this->makeFilePath(str_replace($appRoot, '', $filePath));
        $appendFiles[$filePath] = $data;

        return true;
    });

    artisan(GenerateServer::class, $parameters);

    $appendData = [
        'POST /resources:test-generate-without-properties 200',
        'POST /resources:test-empty-rename-request 200',
        'POST /resources:test-rename-request 200',
        'POST /resources:test-laravel-validations-application-json-request 200',
        'POST /resources:test-laravel-validations-multipart-form-data-request 200',
        'POST /resources:test-laravel-validations-non-available-content-type 200',
        'POST /resources:test-generate-resource-bad-response-key 200',
        'POST /resources:test-generate-without-properties 200',
    ];

    assertEquals(isset($appendFiles[$existTest]), $withControllerEntity);

    if ($withControllerEntity) {
        $appendTestData = $appendFiles[$existTest];
        foreach ($appendData as $data) {
            assertStringContainsString($data, $appendTestData);
        }
    }
})->with([
    [['-e' => 'pest_tests'], false],
    [['-e' => 'controllers,pest_tests'], true],
    [['-e' => 'pest_tests,controllers'], true],
    [[], true],
]);
