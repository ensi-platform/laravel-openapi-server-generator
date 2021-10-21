<?php

use Ensi\LaravelOpenApiServerGenerator\Utils\PSR4PathConverter;

it('throws InvalidArgumentException for unregistred namespaces', function () {
    $converter = new PSR4PathConverter(["App\\" =>"/var/www/acme/app"]);
    $converter->namespaceToPath("Foo\\Bar");
})->throws(InvalidArgumentException::class);

it('can convert namespace to path', function (string $argument) {
    $converter = new PSR4PathConverter(["App\\" => "/var/www/acme/app"]);
    $result = $converter->namespaceToPath($argument);
    expect($result)->toEqual("/var/www/acme/app/Foo/Bar");
})->with(["App\\Foo\\Bar", "App\\Foo\\Bar\\"]);

it('can add mappings on the fly', function (string $argument) {
    $converter = new PSR4PathConverter();
    $converter->addMappings(["App\\" => "/var/www/acme/app"]);
    $result = $converter->namespaceToPath($argument);
    expect($result)->toEqual("/var/www/acme/app/Foo/Bar");
})->with(["App\\Foo\\Bar", "App\\Foo\\Bar\\"]);
