<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use RuntimeException;
use stdClass;

class ResourcesGenerator extends BaseGenerator implements GeneratorInterface
{
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
            foreach ($routes as $route) {
                if (!empty($route->{'x-lg-skip-resource-generation'})) {
                    continue;
                }

                if (empty($route->{'x-lg-handler'})) {
                    continue;
                }

                $response = $route->responses->{201} ?? $route->responses->{200} ?? null;
                if (!$response) {
                    continue;
                }

                $responseSchema = $response->content?->{'application/json'}?->schema ?? null;
                if (!$responseSchema) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});

                try {
                    $namespace = $this->getReplacedNamespace($handler->namespace, $replaceFrom, $replaceTo);
                    $className = $responseSchema->{'x-lg-resource-class-name'} ?? $this->getReplacedClassName($handler->class, $replaceFrom, $replaceTo);
                } catch (RuntimeException) {
                    continue;
                }

                list($className, $namespace) = $this->getActualClassNameAndNamespace($className, $namespace);

                if (isset($resources["$namespace\\$className"])) {
                    continue;
                }

                $responseData = $responseSchema;

                $responseKey = $responseSchema->{'x-lg-resource-response-key'} ??
                    $this->options['resources']['response_key'] ??
                    null;
                if ($responseKey) {
                    $responseKeyParts = explode('.', $responseKey);
                    foreach ($responseKeyParts as $responseKeyPart) {
                        $flag = false;
                        do_with_all_of($responseData, function (stdClass $p) use (&$responseData, &$flag, $responseKeyPart, &$className) {
                            if (std_object_has($p, 'properties')) {
                                if (std_object_has($p->properties, $responseKeyPart)) {
                                    $responseData = $p->properties->$responseKeyPart;
                                    $flag = true;

                                    if (std_object_has($p->properties->$responseKeyPart, 'x-lg-resource-class-name')) {
                                        $className = $p->properties->$responseKeyPart->{'x-lg-resource-class-name'};
                                    }
                                }
                            }
                        });

                        if (!$flag) {
                            $responseData = null;

                            break;
                        }
                    }
                }

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

            $this->putWithDirectoryCheck(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ properties }}' => $properties,
                ])
            );
        }
    }

    private function getProperties(stdClass $object): array
    {
        $properties = [];

        do_with_all_of($object, function (stdClass $p) use (&$properties) {
            if (std_object_has($p, 'properties')) {
                $properties = array_merge($properties, array_keys(get_object_vars($p->properties)));
            }

            if (std_object_has($p, 'items')) {
                $properties = array_merge($properties, $this->getProperties($p->items));
            }
        });

        return $properties;
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
