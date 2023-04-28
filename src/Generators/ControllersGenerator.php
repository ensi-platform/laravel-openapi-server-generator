<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\Utils\ClassParser;
use stdClass;

class ControllersGenerator extends BaseGenerator implements GeneratorInterface
{
    public const REQUEST_NAMESPACE = 'Illuminate\Http\Request';
    public const RESPONSABLE_NAMESPACE = 'Illuminate\Contracts\Support\Responsable';
    public const DELIMITER = "\n    ";

    private array $methodsWithRequests = ['PATCH', 'POST', 'PUT', 'DELETE'];

    public function generate(SpecObjectInterface $specObject): void
    {
        $controllers = $this->extractControllers($specObject);
        $this->createControllersFiles($controllers);
    }

    private function extractControllers(SpecObjectInterface $specObject): array
    {
        $openApiData = $specObject->getSerializableData();

        $controllers = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $method => $route) {
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
                    $namespace = $this->getReplacedNamespace($handler->namespace, 'Controllers', 'Requests') . '\\' .  ucfirst($requestClassName);
                    $controllers[$fqcn]['requestsNamespaces'][$namespace] = $namespace;
                }

                $controllers[$fqcn]['actions'][] = [
                    'name' => $handler->method ?: '__invoke',
                    'with_request_namespace' => $methodWithRequest && !empty($route->{'x-lg-skip-request-generation'}),
                    'parameters' => array_merge($this->extractPathParameters($route), $this->getActionExtraParameters($methodWithRequest, $requestClassName)),
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

    private function getActionExtraParameters(bool $methodWithRequest, $requestClassName = null): array
    {
        if ($methodWithRequest) {
            return [[
                'name' => 'request',
                'type' => $requestClassName ?? 'Request',
            ]];
        }

        return [];
    }

    private function createControllersFiles(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $namespace = $controller['namespace'];
            $className = $controller['className'];

            $filePath = $this->getNamespacedFilePath($className, $namespace);
            if (!$this->filesystem->exists($filePath)) {
                $this->createEmptyControllerFile($filePath, $controller);
            }

            $class = new ClassParser(
                filesystem: $this->filesystem,
                className: "$namespace\\$className"
            );

            $newMethods = $this->convertMethodsToString($class, $controller['actions'], $controller['requestsNamespaces']);
            if (!empty($newMethods)) {
                $controller['requestsNamespaces'][static::RESPONSABLE_NAMESPACE] = static::RESPONSABLE_NAMESPACE;
            }

            $content = $class->getContentWithAdditionalMethods($newMethods, $controller['requestsNamespaces']);

            $this->writeControllerFile($filePath, $controller, $content);
        }
    }

    protected function writeControllerFile(string $filePath, array $controller, string $classContent): void
    {
        $template = $this->templatesManager->getTemplate('ControllerExists.template');

        $this->putWithDirectoryCheck(
            $filePath,
            $this->replacePlaceholders(
                $template,
                [
                    '{{ namespace }}' => $controller['namespace'],
                    '{{ requestsNamespaces }}' => $this->formatRequestNamespaces($controller['requestsNamespaces']),
                    '{{ classContent }}' => $classContent,
                ]
            )
        );
    }

    protected function createEmptyControllerFile(string $filePath, array $controller): void
    {
        $this->putWithDirectoryCheck(
            $filePath,
            $this->replacePlaceholders(
                $this->templatesManager->getTemplate('ControllerEmpty.template'),
                [
                    '{{ namespace }}' => $controller['namespace'],
                    '{{ requestsNamespaces }}' => $this->formatRequestNamespaces($controller['requestsNamespaces']),
                    '{{ className }}' => $controller['className'],
                ]
            )
        );
    }

    private function formatActionParamsAsString(array $params): string
    {
        return implode(', ', array_map(fn (array $param) => $param['type'] . " $" . $param['name'], $params));
    }

    private function convertMethodsToString(ClassParser $class, array $methods, array &$namespaces): string
    {
        $methodsStrings = [];

        foreach ($methods as $method) {
            if ($class->hasMethod($method['name'])) {
                continue;
            }

            if ($method['with_request_namespace']) {
                $namespaces[static::REQUEST_NAMESPACE] = static::REQUEST_NAMESPACE;
            }

            $methodsStrings[] = $this->replacePlaceholders(
                $this->templatesManager->getTemplate('ControllerMethod.template'),
                [
                    '{{ method }}' => $method['name'],
                    '{{ params }}' => $this->formatActionParamsAsString($method['parameters']),
                ]
            );
        }

        $prefix = !empty($methodsStrings) ? static::DELIMITER : '';

        return $prefix . implode(static::DELIMITER, $methodsStrings);
    }

    protected function formatRequestNamespaces(array $namespaces): string
    {
        $namespaces = array_values($namespaces);
        sort($namespaces, SORT_STRING | SORT_FLAG_CASE);

        return implode("\n", array_map(fn (string $namespaces) => "use {$namespaces};", $namespaces));
    }
}
