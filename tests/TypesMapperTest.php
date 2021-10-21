<?php

use Ensi\LaravelOpenApiServerGenerator\Utils\TypesMapper;

it('can convert openapi types to php', function (string $input, string $expected) {
    $result = (new TypesMapper())->openApiToPhp($input);
    expect($result)->toEqual($expected);
})->with([
    ['integer', 'int'],
    ['boolean', 'bool'],
    ['string', 'string'],
    ['number', 'int|float'],
    ['array', 'array'],
    ['object', 'object'],
    ['foo', 'mixed'],
]);
