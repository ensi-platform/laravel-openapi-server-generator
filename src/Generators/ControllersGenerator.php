<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\Data\ReflectionClassData;
use stdClass;

class ControllersGenerator extends BaseGenerator implements GeneratorInterface
{
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

                $requestClassName = $route->{'x-lg-request-class-name'} ?? ucfirst($route->operationId) . 'Request';
                if ($methodWithRequest && empty($route->{'x-lg-skip-request-generation'})) {
                    $controllers[$fqcn]['requestsNamespaces'][] = $this->getReplacedNamespace($handler->namespace, 'Controllers', 'Requests') . '\\' .  ucfirst($requestClassName);
                } elseif ($methodWithRequest && !$requestClassName) {
                    $controllers[$fqcn]['requestsNamespaces'][] = 'Illuminate\Http\Request';
                }

                $controllers[$fqcn]['actions'][] = [
                    'name' => $handler->method ?: '__invoke',
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

            $ref = new ReflectionClassData($className, $namespace);
            $methodsString = $this->convertMethodsToString($ref, $controller['actions']);

            $currentLine = 0;
            $classContent = '';
            $classEndLine = $ref->getEndLine();
            $classStartLine = $ref->getStartLine();
            foreach ($this->filesystem->lines($filePath) as $line) {
                $currentLine++;
                $isEndLine = $currentLine === $classEndLine;
                $isClassContentLines = $currentLine >= $classStartLine;
                $isNamespaceLines = $currentLine < $classStartLine;

                if ($isNamespaceLines) {
                    preg_match('/^use (.*);$/', $line, $matches);
                    $namespace = $matches[1] ?? null;
                    if ($namespace && !in_array($namespace, $controller['requestsNamespaces'])) {
                        $controller['requestsNamespaces'][] = $namespace;
                    }
                }

                if (!$isClassContentLines) {
                    continue;
                }

                if ($isEndLine) {
                    $methodsString = $ref->isEmpty() ? ltrim($methodsString, "\n") : $methodsString;
                    $classContent .= $methodsString . $line;

                    break;
                }

                $classContent .= "$line\n";
            }

            $this->writeControllerFile($filePath, $controller, $classContent);
        }
    }

    protected function writeControllerFile(string $filePath, array $controller, string $classContent): void
    {
        $template = $this->templatesManager->getTemplate('ControllerExists.template');

        $this->putWithDirectoryCheck(
            $filePath,
            $this->replacePlaceholders($template, [
                '{{ namespace }}' => $controller['namespace'],
                '{{ requestsNamespaces }}' => $this->formatRequestNamespaces($controller['requestsNamespaces']),
                '{{ classContent }}' => $classContent,
            ])
        );
    }

    protected function createEmptyControllerFile(string $filePath, array $controller): void
    {
        $template = $this->templatesManager->getTemplate('ControllerEmpty.template');

        $this->putWithDirectoryCheck(
            $filePath,
            $this->replacePlaceholders($template, [
                '{{ namespace }}' => $controller['namespace'],
                '{{ requestsNamespaces }}' => $this->formatRequestNamespaces($controller['requestsNamespaces']),
                '{{ className }}' => $controller['className'],
            ])
        );
    }

    private function formatActionParamsAsString(array $params): string
    {
        return implode(', ', array_map(fn (array $param) => $param['type'] . " $" . $param['name'], $params));
    }

    private function convertMethodsToString(ReflectionClassData $ref, array $methods): string
    {
        $methodsStrings = [];

        foreach ($methods as $method) {
            if ($ref->hasMethod($method['name'])) {
                continue;
            }

            $methodsStrings[] = $this->replacePlaceholders(
                $this->templatesManager->getTemplate('ControllerMethod.template'),
                [
                    '{{ method }}' => $method['name'],
                    '{{ params }}' => $this->formatActionParamsAsString($method['parameters']),
                ]
            );
        }

        return implode("\n\n    ", $methodsStrings);
    }

    protected function formatRequestNamespaces(array $namespaces): string
    {
        $namespaces = array_values($namespaces);
        sort($namespaces, SORT_STRING | SORT_FLAG_CASE);

        return implode("\n", array_map(fn (string $namespaces) => "use {$namespaces};", $namespaces));
    }
}
