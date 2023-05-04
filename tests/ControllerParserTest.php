<?php

use Ensi\LaravelOpenApiServerGenerator\Tests\expects\Controllers\LaravelEmptyController;
use Ensi\LaravelOpenApiServerGenerator\Tests\expects\Controllers\LaravelExistsController;
use Ensi\LaravelOpenApiServerGenerator\Utils\ClassParser;
use Illuminate\Filesystem\Filesystem;

test('Controller check isEmpty success', function (string $namespace, bool $result) {
    $filesystem = $this->mock(Filesystem::class);

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    expect($parser->isEmpty())->toBe($result);
})->with([
    [LaravelExistsController::class, false],
    [LaravelEmptyController::class, true],
]);

test('Controller check getMethods success', function (string $namespace, array $result) {
    $filesystem = $this->mock(Filesystem::class);

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    $methods = $parser->getMethods()->keys()->toArray();

    expect($methods)->toBe($result);
})->with([
    [LaravelExistsController::class, ['delete']],
    [LaravelEmptyController::class, []],
]);

test('Controller check hasMethod success', function (string $namespace, string $method, bool $result) {
    $filesystem = $this->mock(Filesystem::class);

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    expect($parser->hasMethod($method))->toBe($result);
})->with([
    [LaravelExistsController::class, 'delete', true],
    [LaravelExistsController::class, 'search', false],
    [LaravelEmptyController::class, 'delete', false],
    [LaravelEmptyController::class, 'search', false],
]);

test('Controller check getLines success', function (string $namespace, int $start, int $end) {
    $filesystem = $this->mock(Filesystem::class);

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    expect($parser->getStartLine())->toBe($start);
    expect($parser->getEndLine())->toBe($end);
})->with([
    [LaravelExistsController::class, 8, 14],
    [LaravelEmptyController::class, 5, 10],
]);

test('Controller check getFileName success', function (string $namespace) {
    $filesystem = $this->mock(Filesystem::class);

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    $class = last(explode("\\", $namespace));

    expect($parser->getFileName())->toBe(realpath(__DIR__ . "/expects/Controllers/{$class}.php"));
})->with([
    [LaravelExistsController::class],
    [LaravelEmptyController::class],
]);

test('Controller check getContentWithAdditionalMethods success', function (
    string $namespace,
    string $expect,
    string $additional = "",
    array $namespaces = [],
    array $expectNamespaces = [],
) {
    $class = last(explode("\\", $namespace));

    /** @var \Mockery\Mock|Filesystem $filesystem */
    $filesystem = $this->mock(Filesystem::class);
    $filesystem
        ->shouldReceive('lines')
        ->andReturn(file(__DIR__ . "/expects/Controllers/{$class}.php", FILE_IGNORE_NEW_LINES));

    $parser = new ClassParser($filesystem);
    $parser->parse($namespace);

    $content = $parser->getContentWithAdditionalMethods($additional, $namespaces);
    $result = trim(file_get_contents(__DIR__ . "/expects/Controllers/{$expect}.expect"));

    expect($content)->toBe($result);

    expect($namespaces)->toBe($expectNamespaces);
})->with([
    [
        LaravelExistsController::class,
        'LaravelExists_1_Controller',
        "\n    public function test() {}\n",
        [
            "App\Http\ApiV1\Support\Resources\EmptyResource" =>  "App\Http\ApiV1\Support\Resources\EmptyResource",
        ],
        [
            "App\Http\ApiV1\Support\Resources\EmptyResource" =>  "App\Http\ApiV1\Support\Resources\EmptyResource",
            "Illuminate\Contracts\Support\Responsable" =>  "Illuminate\Contracts\Support\Responsable",
            "Illuminate\Http\Request" =>  "Illuminate\Http\Request",
        ],
    ],
    [
        LaravelExistsController::class,
        'LaravelExists_2_Controller',
        "",
        [],
        [
            "Illuminate\Contracts\Support\Responsable" =>  "Illuminate\Contracts\Support\Responsable",
            "Illuminate\Http\Request" =>  "Illuminate\Http\Request",
        ],
    ],
    [LaravelEmptyController::class, 'LaravelEmpty_1_Controller'],
    [LaravelEmptyController::class, 'LaravelEmpty_2_Controller', "\n    public function test() {}\n"],
]);
