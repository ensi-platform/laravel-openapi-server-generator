<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

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

        $controllerNamespaces = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $path => $routes) {
            foreach ($routes as $method => $route) {
                $handler = $route->{'x-lg-handler'} ?? null;
                $routeName = $route->{'x-lg-route-name'} ?? null;
                $routeMiddleware = $route->{'x-lg-middleware'} ?? null;
                $routeWithoutMiddleware = $route->{'x-lg-without-middleware'} ?? null;
                if ($handler) {
                    $handler = $this->getControllerInfoFromHandler($controllerNamespaces, $handler);

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

    private function getControllerInfoFromHandler(array &$controllerNamespaces, string $handler): string
    {
        $parsedRouteHandler = $this->routeHandlerParser->parse($handler);
        $class = $parsedRouteHandler->class . '::class';
        $method = $parsedRouteHandler->method;

        if (isset($controllerNamespaces[$parsedRouteHandler->class])) {
            if (!isset($controllerNamespaces[$parsedRouteHandler->class]['items'][$parsedRouteHandler->namespace])) {
                $count = ++$controllerNamespaces[$parsedRouteHandler->class]['count'];
                $class = "{$parsedRouteHandler->class}{$count}";
                $controllerNamespaces[$parsedRouteHandler->class] = [
                    'items' => array_merge(
                        $controllerNamespaces[$parsedRouteHandler->class]['items'],
                        [
                            $parsedRouteHandler->namespace => [
                                'class_name' => $class,
                                'namespace' => "{$parsedRouteHandler->namespace}\\{$parsedRouteHandler->class} as {$class}",
                            ],
                        ]
                    ),
                    'count' => $count,
                ];
                $class = $class . '::class';
            } else {
                $class = $controllerNamespaces[$parsedRouteHandler->class]['items'][$parsedRouteHandler->namespace]['class_name'] . '::class';
            }
        } else {
            $controllerNamespaces[$parsedRouteHandler->class] = [
                'items' => [
                    $parsedRouteHandler->namespace => [
                        'class_name' => $parsedRouteHandler->class,
                        'namespace' => "{$parsedRouteHandler->namespace}\\{$parsedRouteHandler->class}",
                    ],
                ],
                'count' => 1,
            ];
        }

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

    private function formatControllerNamespaces(array $controllerNamespaces): string
    {
        $namespaces = [];
        foreach ($controllerNamespaces as $controllerNamespacesByClassName) {
            foreach ($controllerNamespacesByClassName['items'] as $controllerNamespace) {
                $namespaces[] = $controllerNamespace['namespace'];
            }
        }

        sort($namespaces);

        return implode("\n", array_map(fn (string $namespace) => "use {$namespace};", $namespaces));
    }
}
