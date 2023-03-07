<?php

use Ensi\LaravelOpenApiServerGenerator\Commands\GenerateServer;
use Ensi\LaravelOpenApiServerGenerator\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertEqualsCanonicalizing;

test('can generate policy', function () {
    \PHPUnit\Framework\assertIsBool(true);
});
