<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

use Ensi\LaravelOpenApiServerGenerator\DTO\ParsedRouteHandler;

class RouteHandlerParser
{
    public function parse(string $handler): ParsedRouteHandler
    {
        $parts = preg_split('/(@|::)/', $handler, -1, PREG_SPLIT_NO_EMPTY);
        $method = count($parts) > 1 ? $parts[1] : null;

        $fqcn = ltrim($parts[0], '\\');
        $fqcnParts = explode("\\", $fqcn);

        $class = array_pop($fqcnParts);
        $namespace = $fqcnParts ? implode("\\", $fqcnParts) : null;

        return new ParsedRouteHandler(
            namespace: $namespace,
            class: $class,
            fqcn: $fqcn,
            method: $method,
        );
    }
}
