<?php

namespace Greensight\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use stdClass;

class ControllersGenerator extends BaseGenerator implements GeneratorInterface
{
    private array $methodsWithRequests = ['PATCH', 'POST', 'PUT', 'DELETE'];

    public function generate(SpecObjectInterface $specObject): void
    {
        $controllers = $this->extractControllers($specObject);
        $this->createControllersFiles($controllers, $this->templatesManager->getTemplate('Controller.template'));
    }

    private function extractControllers(SpecObjectInterface $specObject): array
    {
        $openApiData = $specObject->getSerializableData();

        $controllers = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $method => $route) {
                if (!empty($route->{'x-lg-skip-controller-generation'})) {
                    continue;
                }

                if (empty($route->{'x-lg-handler'})) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});
                $fqcn = $handler->fqcn;
                if (!$fqcn) {
                    continue;
                }

                if (!isset($controllers[$fqcn])) {
                    $controllers[$fqcn] = [
                        'className' => $handler->class,
                        'namespace' => $handler->namespace,
                        'actions' => [],
                    ];
                }

                $controllers[$fqcn]['actions'][] = [
                    'name' => $handler->method ?: '__invoke',
                    'parameters' => array_merge($this->extractPathParameters($route), $this->getActionExtraParameters($method)),
                ];
            }
        }

        return $controllers;
    }

    private function extractPathParameters(stdClass $route): array
    {
        $oasRoutePath =  array_filter($route->parameters ?? [], fn (stdClass $param) => $param?->in === "path");

        return array_map(fn (stdClass $param) => [
            'name' => $param->name,
            'type' => $this->typesMapper->openApiToPhp($param?->schema?->type ?? ''),
        ], $oasRoutePath);
    }

    private function getActionExtraParameters(string $method): array
    {
        return in_array(strtoupper($method), $this->methodsWithRequests)
            ? [['name' => 'request', 'type' => 'Request']]
            : [];
    }

    private function createControllersFiles(array $controllers, string $template): void
    {
        foreach ($controllers as $controller) {
            $namespace = $controller['namespace'];
            $className = $controller['className'];

            $filePath = $this->getNamespacedFilePath($className, $namespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $methodsString = '';
            foreach ($controller['actions'] as $action) {
                $methodName = $action['name'];
                $paramsString = $this->formatActionParamsAsString($action['parameters']);
                $methodsString .= <<<METHOD

                    public function {$methodName}({$paramsString}) 
                    {
                        //
                    }

                METHOD;
            }
            $methodsString = trim($methodsString, "\n");

            $this->filesystem->put(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ methods }}' => $methodsString,
                ])
            );
        }
    }

    private function formatActionParamsAsString(array $params): string
    {
        return implode(', ', array_map(fn (array $param) => $param['type'] . " $" . $param['name'], $params));
    }
}
