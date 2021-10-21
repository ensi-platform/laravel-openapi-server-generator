<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

class TypesMapper
{
    private array $mappings = [
        'integer' => 'int',
        'boolean' => 'bool',
        'string' => 'string',
        'number' => 'int|float',
        'array' => 'array',
        'object' => 'object',
    ];

    public function openApiToPhp(string $type): string
    {
        return $this->mappings[$type] ?? 'mixed';
    }
}
