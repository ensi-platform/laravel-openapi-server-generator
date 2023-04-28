<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\Data\OpenApi3\OpenApi3Schema;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3ContentTypeEnum;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RequestsGenerator extends BaseGenerator implements GeneratorInterface
{
    private array $methods = ['PATCH', 'POST', 'PUT', 'DELETE'];

    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['requests']['namespace'] ?? null;
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

                list($className, $newNamespace) = $this->getActualClassNameAndNamespace($className, $newNamespace);

                $validationRules = '//';
                $usesEnums = '';
                if (std_object_has($route, 'requestBody')) {
                    $contentType = OpenApi3ContentTypeEnum::tryFrom(array_keys(get_object_vars($route->requestBody->content))[0]);
                    if ($contentType) {
                        try {
                            [$validationRules, $usesEnums] = $this->getPropertyRules($contentType, $route->requestBody);
                        } catch (Throwable $e) {
                            console_warning("$className didn't generate", $e);
                        }
                    }
                }

                $requests[] = compact('className', 'newNamespace', 'validationRules', 'usesEnums');
            }
        }

        return $requests;
    }

    protected function getPropertyRules(OpenApi3ContentTypeEnum $contentType, $requestBody): array
    {
        $request = new OpenApi3Schema();
        $request->fillFromStdRequestBody($contentType, $requestBody);

        return $request->object->toLaravelValidationRules($this->options);
    }

    protected function createRequestsFiles(array $requests, string $template): void
    {
        foreach ($requests as [
            'className' => $className,
            'newNamespace' => $newNamespace,
            'validationRules' => $validationRules,
            'usesEnums' => $usesEnums
        ]) {
            $filePath = $this->getNamespacedFilePath($className, $newNamespace);
            if ($this->filesystem->exists($filePath)) {
                continue;
            }

            $this->putWithDirectoryCheck(
                $filePath,
                $this->replacePlaceholders(
                    $template,
                    [
                    '{{ namespace }}' => $newNamespace,
                    '{{ uses }}' => $usesEnums,
                    '{{ className }}' => $className,
                    '{{ rules }}' => $validationRules,
                ],
                    true
                )
            );
        }
    }
}
