<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use RuntimeException;
use stdClass;

class ResourcesGenerator extends BaseGenerator implements GeneratorInterface
{
    private array $methods = ['GET', 'PATCH', 'POST', 'PUT'];

    public function generate(SpecObjectInterface $specObject): void
    {
        $resources = $this->extractResources($specObject);
        $this->createResourcesFiles($resources, $this->templatesManager->getTemplate('Resource.template'));
    }

    protected function extractResources(SpecObjectInterface $specObject): array
    {
        $replaceFrom = 'Controller';
        $replaceTo = 'Resource';

        $openApiData = $specObject->getSerializableData();

        $resources = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $method => $route) {
                if (!in_array(strtoupper($method), $this->methods) || !empty($route->{'x-lg-skip-resource-generation'})) {
                    continue;
                }

                if (empty($route->{'x-lg-handler'})) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});

                try {
                    $namespace = $this->getReplacedNamespace($handler->namespace, $replaceFrom, $replaceTo);
                    $className = $route->{'x-lg-resource-class-name'} ?? $this->getReplacedClassName($handler->class, $replaceFrom, $replaceTo);
                } catch (RuntimeException) {
                    continue;
                }

                if (isset($resources["$namespace\\$className"])) {
                    continue;
                }

                $response = $route->responses->{201} ?? $route->responses->{200} ?? null;
                if (!$response) {
                    continue;
                }

                $responseData = $response->content?->{'application/json'}?->schema?->properties?->data ?? null;
                if (!$responseData) {
                    continue;
                }

                $properties = $this->convertToString($this->getProperties($responseData));

                if (empty($properties)) {
                    continue;
                }

                $resources["$namespace\\$className"] = compact('className', 'namespace', 'properties');
            }
        }

        return $resources;
    }

    protected function createResourcesFiles(array $resources, string $template): void
    {
        foreach ($resources as ['className' => $className, 'namespace' => $namespace, 'properties' => $properties]) {
            $filePath = $this->getNamespacedFilePath($className, $namespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $this->filesystem->put(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ properties }}' => $properties,
                ])
            );
        }
    }

    private function getProperties(stdClass $responseData): array
    {
        if (isset($responseData->type) && $responseData->type == 'array') {
            $responseData = $responseData->items;
        }

        if (isset($responseData->allOf)) {
            $properties = [];

            /** @var stdClass $partResponseData */
            foreach ($responseData->allOf as $partResponseData) {
                if (!isset($partResponseData->properties)) {
                    continue;
                }
                $properties = array_merge($properties, $this->getProperties($partResponseData));
            }

            return $properties;
        }

        if (!isset($responseData->properties)) {
            return [];
        }

        return array_keys(get_object_vars($responseData->properties));
    }

    private function convertToString(array $properties): string
    {
        $propertyStrings = [];

        foreach ($properties as $property) {
            $propertyStrings[] = "'$property' => \$this->$property,";
        }

        return implode("\n            ", $propertyStrings);
    }
}
