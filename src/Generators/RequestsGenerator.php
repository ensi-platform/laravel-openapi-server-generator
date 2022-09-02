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

                $propertyRules = $this->getPropertyRules($route->requestBody);

                $requests[] = compact('className', 'newNamespace', 'propertyRules');
            }
        }

        return $requests;
    }
    
    protected function getPropertyRules($requestBody): string
    {
        $properties = [];

        $contentType = array_keys(get_object_vars($requestBody->content))[0];
        switch ($contentType) {
            case 'application/json':
                foreach ($requestBody->content->{'application/json'}->schema->allOf as $object) {
                    if (isset(get_object_vars($object)['properties'])) {
                        foreach (get_object_vars($object->properties) as $propertyName => $property) {
                            $properties[$propertyName] = [
                                'type' => $property->type,
                            ];

                            if (isset(get_object_vars($property)['nullable'])) {
                                $properties[$propertyName]['nullable'] = true;
                            }
                        }
                    } elseif (isset(get_object_vars($object)['required'])) {
                        foreach ($object->required as $requiredProperty) {
                            if (isset($properties[$requiredProperty])) {
                                $properties[$requiredProperty]['required'] = true;
                            } else {
                                $properties[$requiredProperty] = [
                                    'required' => true,
                                ];
                            }
                        }
                    }
                }

                break;
            default:
        }

        return $this->toLaravelValidationsAndFormat($properties);
    }

    protected function toLaravelValidationsAndFormat(array $properties): string
    {
        $laravelValidationRules = [];
        foreach ($properties as $propertyName => $property) {
            $validations = [];
            if (isset($property['required'])) {
                $validations[] = "'required'";
            }
            if (isset($property['nullable'])) {
                $validations[] = "'nullable'";
            }
            $validations[] = "'{$property['type']}'";
            $validationsString = implode(', ', $validations);
            $laravelValidationRules[] = "'{$propertyName}' => [{$validationsString}],";
        }

        return implode("\n            ", $laravelValidationRules);
    }

    protected function createRequestsFiles(array $requests, string $template): void
    {
        foreach ($requests as ['className' => $className, 'newNamespace' => $newNamespace, 'propertyRules' => $propertyRules]) {
            $filePath = $this->getNamespacedFilePath($className, $newNamespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $this->filesystem->put(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $newNamespace,
                    '{{ className }}' => $className,
                    '{{ rules }}' => $propertyRules,
                ])
            );
        }
    }
}
