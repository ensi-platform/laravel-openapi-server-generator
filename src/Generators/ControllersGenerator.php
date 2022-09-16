<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

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
                $requestName = null;
                $requestClassName = null;
                $methodWithRequest = in_array(strtoupper($method), $this->methodsWithRequests);

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
                        'requestsNamespaces' => [],
                    ];
                }

                if ($methodWithRequest && empty($route->{'x-lg-skip-request-generation'})) {
                    $requestClassName = $route->{'x-lg-request-class-name'} ?? ucfirst($route->operationId) . 'Request';
                    $requestName = lcfirst($requestClassName);
                    $controllers[$fqcn]['requestsNamespaces'][] = $this->getReplacedNamespace($handler->namespace, 'Controllers', 'Requests') . '\\' .  ucfirst($requestClassName);
                } elseif ($methodWithRequest) {
                    $controllers[$fqcn]['requestsNamespaces'][] = 'Illuminate\Http\Request';
                }

                $controllers[$fqcn]['actions'][] = [
                    'name' => $handler->method ?: '__invoke',
                    'parameters' => array_merge($this->extractPathParameters($route), $this->getActionExtraParameters($methodWithRequest, $requestName, $requestClassName)),
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

    private function getActionExtraParameters(bool $methodWithRequest, $requestName = null, $requestClassName = null): array
    {
        if ($methodWithRequest) {
            return [[
                'name' => $requestName ?? 'request',
                'type' => $requestClassName ?? 'Request',
            ]];
        }

        return [];
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
                    '{{ requestsNamespaces }}' => $this->formatNamespaces($controller['requestsNamespaces']),
                ])
            );
        }
    }

    private function formatActionParamsAsString(array $params): string
    {
        return implode(', ', array_map(fn (array $param) => $param['type'] . " $" . $param['name'], $params));
    }
}
