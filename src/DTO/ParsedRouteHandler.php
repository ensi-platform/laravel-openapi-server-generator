<?php

namespace Ensi\LaravelOpenApiServerGenerator\DTO;

class ParsedRouteHandler
{
    public function __construct(
        public ?string $namespace,
        public string $class,
        public string $fqcn,
        public ?string $method,
    ) {
    }
}
