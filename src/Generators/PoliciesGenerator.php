<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\DTO\ParsedRouteHandler;
use Ensi\LaravelOpenApiServerGenerator\Utils\ClassParser;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

class PoliciesGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['policies']['namespace'] ?? null;
        if (!is_array($namespaceData)) {
            throw new InvalidArgumentException("PoliciesGenerator must be configured with array as 'namespace'");
        }

        $policies = $this->extractPolicies($specObject, $namespaceData);
        $this->createPoliciesFiles($policies, $this->templatesManager->getTemplate('Policy.template'));
    }

    protected function extractPolicies(SpecObjectInterface $specObject, array $namespaceData): array
    {
        $replaceFromNamespace = array_keys($namespaceData)[0];
        $replaceToNamespace = array_values($namespaceData)[0];

        $openApiData = $specObject->getSerializableData();

        $policies = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $route) {
                if (!$this->routeValidation($route)) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});
                if (!$this->handlerValidation($handler)) {
                    continue;
                }

                try {
                    $namespace = $this->getReplacedNamespace(
                        $handler->namespace,
                        $replaceFromNamespace,
                        $replaceToNamespace
                    );
                } catch (RuntimeException) {
                    continue;
                }

                $className = $handler->class . 'Policy';
                $methods = [$handler->method];

                if (isset($policies["$namespace\\$className"])) {
                    $policies["$namespace\\$className"]['methods'][] = $methods[0];
                } else {
                    $policies["$namespace\\$className"] = compact('className', 'namespace', 'methods');
                }
            }
        }

        return $policies;
    }

    protected function createPoliciesFiles(array $policies, string $template): void
    {
        foreach ($policies as ['className' => $className, 'namespace' => $namespace, 'methods' => $methods]) {
            $filePath = $this->getNamespacedFilePath($className, $namespace);
            if ($this->filesystem->exists($filePath)) {
                $class = $this->classParser->parse("$namespace\\$className");

                $newPolicies = $this->convertMethodsToString($methods, $class);
                if (!empty($newPolicies)) {
                    $class->addMethods($newPolicies);
                }

                continue;
            }

            $this->putWithDirectoryCheck(
                $filePath,
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ methods }}' => $this->convertMethodsToString($methods),
                ])
            );
        }
    }

    private function routeValidation(stdClass $route): bool
    {
        return match (true) {
            !empty($route->{'x-lg-skip-policy-generation'}),
            empty($route->{'x-lg-handler'}),
            empty($route->responses->{403}) => false,
            default => true
        };
    }

    private function handlerValidation(ParsedRouteHandler $handler): bool
    {
        return match (true) {
            empty($handler->namespace),
            empty($handler->class),
            empty($handler->method) => false,
            default => true
        };
    }

    private function convertMethodsToString(array $methods, ?ClassParser $class = null): string
    {
        $methodsStrings = [];

        foreach ($methods as $method) {
            if ($class?->hasMethod($method)) {
                continue;
            }

            $methodsStrings[] = $this->replacePlaceholders(
                $this->templatesManager->getTemplate('PolicyGate.template'),
                ['{{ method }}' => $method]
            );
        }

        if ($class) {
            $existMethods = $class->getMethods();
            foreach ($existMethods as $methodName => $method) {
                if (!in_array($methodName, $methods) && !$class->isTraitMethod($methodName)) {
                    $className = $class->getClassName();
                    console_warning("Warning: метод {$className}::{$methodName} отсутствует в спецификации или не может возвращать 403 ошибку");
                }
            }
        }

        return implode("\n\n    ", $methodsStrings);
    }
}
