<?php

namespace Greensight\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;

class RoutesGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['namespace'] ?? null;
        if (!is_string($namespaceData)) {
            throw new InvalidArgumentException("RoutesGenerator must be configured with string as 'namespace'");
        }

        $namespace = rtrim($namespaceData, "\\");
        $openApiData = $specObject->getSerializableData();

        $routesStrings = '';

        $paths = $openApiData->paths ?: [];
        foreach ($paths as $path => $routes) {
            foreach ($routes as $method => $route) {
                $handler = $route->{'x-lg-handler'} ?? null;
                $routeName = $route->{'x-lg-route-name'} ?? null;
                $routeMiddleware = $route->{'x-lg-middleware'} ?? null;
                if ($handler) {
                    $routesStrings .= "Route::{$method}('{$this->trimPath($path)}', {$this->formatHandler($handler)})";
                    $routesStrings .= $routeName ? "->name('{$routeName}')": "";
                    $routesStrings .= $routeMiddleware ? "->middleware({$this->formatMiddleware($routeMiddleware)})": "";
                    $routesStrings .= ";\n";
                }
            }
        }

        $routesPath = $this->getNamespacedFilePath("routes", $namespace);
        if ($this->filesystem->exists($routesPath)) {
            $this->filesystem->delete($routesPath);
        }

        $template = $this->templatesManager->getTemplate('routes.template');
        $this->filesystem->put(
            $routesPath,
            $this->replacePlaceholders($template, ['{{ routes }}' => $routesStrings])
        );
    }

    private function formatHandler(string $handler): string
    {
        $parsedRouteHandler = $this->routeHandlerParser->parse($handler);
        $class = '\\' . $parsedRouteHandler->fqcn . '::class';
        $method = $parsedRouteHandler->method;

        return $method ? "[$class, '$method']" : "$class";
    }

    private function formatMiddleware(string $middleware): string
    {
        $parts = array_map(function ($m) {
            $trimmedMiddleware = trim($m);

            return str_ends_with($trimmedMiddleware, '::class') ? "{$trimmedMiddleware}" : "'{$trimmedMiddleware}'";
        }, explode(",", $middleware));

        return '[' . implode(', ', $parts) . ']';
    }
}
