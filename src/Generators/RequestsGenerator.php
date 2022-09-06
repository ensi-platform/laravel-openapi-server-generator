<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\Data\ObjectProperty;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3ContentTypeEnum;
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

                [$propertyRules, $usesEnums] = $this->getPropertyRules($route->requestBody);

                $requests[] = compact('className', 'newNamespace', 'propertyRules', 'usesEnums');
            }
        }

        return $requests;
    }

    protected function getPropertyRules($requestBody): array
    {
        /** @var ObjectProperty[] $properties */
        $properties = [];
        $contentType = array_keys(get_object_vars($requestBody->content))[0];
        switch ($contentType) {
            case OpenApi3ContentTypeEnum::APPLICATION_JSON->value:
                foreach ($requestBody->content->{OpenApi3ContentTypeEnum::APPLICATION_JSON->value}->schema->allOf as $object) {
                    if (isset(get_object_vars($object)['properties'])) {
                        foreach (get_object_vars($object->properties) as $propertyName => $property) {
                            $objectProperty = new ObjectProperty($propertyName, $property->type);

                            if (isset(get_object_vars($property)['required'])) {
                                $objectProperty->required = true;
                            }
                            if (isset(get_object_vars($property)['nullable'])) {
                                $objectProperty->nullable = true;
                            }
                            if (isset(get_object_vars($property)['format'])) {
                                $objectProperty->format = $property->format;
                            }
                            if (isset(get_object_vars($property)['x-lg-enum-class'])) {
                                $objectProperty->enumClass = $property->{'x-lg-enum-class'};
                            }

                            $properties[$propertyName] = $objectProperty;
                        }
                    } elseif (isset(get_object_vars($object)['required'])) {
                        foreach ($object->required as $requiredProperty) {
                            if (isset($properties[$requiredProperty])) {
                                $properties[$requiredProperty]->required = true;
                            } else {
                                $objectProperty = new ObjectProperty($requiredProperty, required: true);
                                $properties[$requiredProperty] = $objectProperty;
                            }
                        }
                    }
                }

                break;
            default:
        }

        return $this->toLaravelValidationsAndFormat($properties);
    }

    /**
     * @param ObjectProperty[] $properties
     * @return array
     */
    protected function toLaravelValidationsAndFormat(array $properties): array
    {
        $propertyRules = [];
        $usesEnums = [];
        foreach ($properties as $property) {
            [$laravelValidationRules, $usesEnum] = $property->toLaravelValidations();

            $propertyRules[] = $laravelValidationRules;
            if ($usesEnum) {
                $usesEnums[] = $usesEnum;
            }
        }

        if ($usesEnums) {
            $usesEnums[] = 'use Illuminate\Validation\Rules\Enum;';
        }
        $usesEnums[] = 'use Illuminate\Foundation\Http\FormRequest;';
        sort($usesEnums);

        return [implode("\n            ", $propertyRules), implode("\n", $usesEnums)];
    }

    protected function createRequestsFiles(array $requests, string $template): void
    {
        foreach ($requests as ['className' => $className, 'newNamespace' => $newNamespace, 'propertyRules' => $propertyRules, 'usesEnums' => $usesEnums]) {
            $filePath = $this->getNamespacedFilePath($className, $newNamespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $this->filesystem->put(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $newNamespace,
                    '{{ uses }}' => $usesEnums,
                    '{{ className }}' => $className,
                    '{{ rules }}' => $propertyRules,
                ])
            );
        }
    }
}
