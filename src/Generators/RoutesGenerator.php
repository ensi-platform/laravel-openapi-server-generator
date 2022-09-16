<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;

class RoutesGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['routes']['namespace'] ?? null;
        if (!is_string($namespaceData)) {
            throw new InvalidArgumentException("RoutesGenerator must be configured with string as 'namespace'");
        }

        $namespace = rtrim($namespaceData, "\\");
        $openApiData = $specObject->getSerializableData();

        $routesStrings = '';

        $controllerNamespaces = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $path => $routes) {
            foreach ($routes as $method => $route) {
                $handler = $route->{'x-lg-handler'} ?? null;
                $routeName = $route->{'x-lg-route-name'} ?? null;
                $routeMiddleware = $route->{'x-lg-middleware'} ?? null;
                $routeWithoutMiddleware = $route->{'x-lg-without-middleware'} ?? null;
                if ($handler) {
                    [$handler, $controllerNamespace] = $this->getControllerInfoFromHandler($handler);

                    $controllerNamespaces[] = $controllerNamespace;

                    $routesStrings .= "Route::{$method}('{$this->trimPath($path)}', {$handler})";
                    $routesStrings .= $routeName ? "->name('{$routeName}')": "";
                    $routesStrings .= $routeMiddleware ? "->middleware({$this->formatMiddleware($routeMiddleware)})": "";
                    $routesStrings .= $routeWithoutMiddleware ? "->withoutMiddleware({$this->formatMiddleware($routeWithoutMiddleware)})": "";
                    $routesStrings .= ";\n";
                }
            }
        }

        $controllerNamespacesStrings = $this->formatControllerNamespaces($controllerNamespaces);

        $routesPath = $this->getNamespacedFilePath("routes", $namespace);
        if ($this->filesystem->exists($routesPath)) {
            $this->filesystem->delete($routesPath);
        }

        $template = $this->templatesManager->getTemplate('routes.template');
        $this->filesystem->put(
            $routesPath,
            $this->replacePlaceholders($template, [
                '{{ controller_namespaces }}' => $controllerNamespacesStrings,
                '{{ routes }}' => $routesStrings,
            ])
        );
    }

    private function getControllerInfoFromHandler(string $handler): array
    {
        $parsedRouteHandler = $this->routeHandlerParser->parse($handler);
        $class = $parsedRouteHandler->class . '::class';
        $method = $parsedRouteHandler->method;

        return [$method ? "[$class, '$method']" : "$class", "{$parsedRouteHandler->namespace}\\{$parsedRouteHandler->class}"];
    }

    private function formatMiddleware(string $middleware): string
    {
        $parts = array_map(function ($m) {
            $trimmedMiddleware = trim($m);

            return str_ends_with($trimmedMiddleware, '::class') ? "{$trimmedMiddleware}" : "'{$trimmedMiddleware}'";
        }, explode(",", $middleware));

        return '[' . implode(', ', $parts) . ']';
    }

    private function formatControllerNamespaces(array $controllerNamespaces): string
    {
        $controllerNamespaces = array_unique($controllerNamespaces);
        sort($controllerNamespaces);

        return implode("\n", array_map(fn (string $controllerNamespace) => "use {$controllerNamespace};", $controllerNamespaces));
    }
}
