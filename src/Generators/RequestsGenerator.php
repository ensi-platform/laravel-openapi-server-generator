<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;
use RuntimeException;

class RequestsGenerator extends BaseGenerator implements GeneratorInterface
{
    private array $methods = ['PATCH', 'POST', 'PUT', 'DELETE'];

    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['namespace'] ?? null;
        if (!is_array($namespaceData)) {
            throw new InvalidArgumentException("RequestsGenerator must be configured with array as 'namespace'");
        }

        $requests = $this->extractRequests($specObject, $namespaceData);
        $this->createRequestsFiles($requests, $this->templatesManager->getTemplate('Request.template'));
    }

    protected function extractRequests(SpecObjectInterface $specObject, array $namespaceData): array
    {
        $replaceFromNamespace = array_keys($namespaceData)[0];
        $replaceToNamespace = array_values($namespaceData)[0];

        $openApiData = $specObject->getSerializableData();

        $requests = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $method => $route) {
                if (!in_array(strtoupper($method), $this->methods) || !empty($route->{'x-lg-skip-request-generation'})) {
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

                $className = $route->{'x-lg-request-class-name'} ?? ucfirst($route->operationId) . 'Request';
                if (!$className) {
                    continue;
                }

                $requests[] = compact('className', 'newNamespace');
            }
        }

        return $requests;
    }

    protected function createRequestsFiles(array $requests, string $template): void
    {
        foreach ($requests as ['className' => $className, 'newNamespace' => $newNamespace]) {
            $filePath = $this->getNamespacedFilePath($className, $newNamespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $this->filesystem->put(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $newNamespace,
                    '{{ className }}' => $className,
                ])
            );
        }
    }
}
