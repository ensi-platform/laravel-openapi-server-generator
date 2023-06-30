<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

abstract class TestsGenerator extends BaseGenerator implements GeneratorInterface
{
    abstract protected function convertRoutesToTestsString(array $routes, string $serversUrl, bool $onlyNewMethods = false): string;

    abstract protected function convertRoutesToImportsString(array $routes): string;

    abstract protected function getTemplateName(): string;

    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['pest_tests']['namespace'] ?? null;
        if (!is_array($namespaceData)) {
            throw new InvalidArgumentException("TestsGenerator must be configured with array as 'namespace'");
        }

        $openApiData = $specObject->getSerializableData();
        $serversUrl = $openApiData?->servers[0]?->url ?? '';
        $tests = $this->constructTests($openApiData, $namespaceData);
        $template = $this->templatesManager->getTemplate($this->getTemplateName());

        $this->createTestsFiles($tests, $template, $serversUrl);
    }

    protected function constructTests(stdClass $openApiData, array $namespaceData): array
    {
        $replaceFromNamespace = array_keys($namespaceData)[0];
        $replaceToNamespace = array_values($namespaceData)[0];

        $tests = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $path => $routes) {
            foreach ($routes as $method => $route) {
                if (!empty($route->{'x-lg-skip-tests-generation'})) {
                    continue;
                }

                if (empty($route->{'x-lg-handler'})) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});

                try {
                    $newNamespace = $this->getReplacedNamespace($handler->namespace, $replaceFromNamespace, $replaceToNamespace);
                } catch (RuntimeException) {
                    continue;
                }


                $className = str_replace("Controller", "", $handler->class) . "ComponentTest";
                if (!$className) {
                    continue;
                }

                $firstResponse = null;
                if (isset($route->responses)) {
                    $firstResponse = current((array)$route->responses) ?? null;
                }
                if (!$firstResponse) {
                    continue;
                }

                $testFqcn = $handler->namespace . "\\". $className;
                if (!isset($tests[$testFqcn])) {
                    $tests[$testFqcn] = [
                        'className' => $className,
                        'namespace' => $newNamespace,
                        'routes' => [],
                    ];
                }

                $tests[$testFqcn]['routes'][] = [
                    'method' => $method,
                    'path' => $path,
                    'responseCodes' => $route->responses ? array_keys(get_object_vars($route->responses)) : [],
                    'responseContentType' => isset($firstResponse->content) ? array_keys(get_object_vars($firstResponse->content))[0] : "",
                ];
            }
        }

        return $tests;
    }

    protected function createTestsFiles(array $testsData, string $template, $serversUrl): void
    {
        foreach ($testsData as ['className' => $className, 'namespace' => $namespace, 'routes' => $routes]) {
            $filePath = $this->getNamespacedFilePath($className, $namespace);
            if ($this->filesystem->exists($filePath)) {
                $newTests = $this->convertRoutesToTestsString($routes, $serversUrl, true);
                if (!empty($newTests)) {
                    $data = <<<TESTS
                        {$newTests}

                    TESTS;

                    $this->filesystem->append($filePath, $data);
                }

                continue;
            }

            $this->putWithDirectoryCheck(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ imports }}' => $this->convertRoutesToImportsString($routes),
                    '{{ tests }}' => $this->convertRoutesToTestsString($routes, $serversUrl),
                ])
            );
        }
    }
}
