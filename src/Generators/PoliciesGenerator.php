<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\DTO\ParsedRouteHandler;
use RuntimeException;
use stdClass;

class PoliciesGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $policies = $this->extractPolicies($specObject);
        $this->createPoliciesFiles($policies, $this->templatesManager->getTemplate('Policy.template'));
    }

    protected function extractPolicies(SpecObjectInterface $specObject): array
    {
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
                        'Controllers',
                        'Policies'
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
                continue;
            }

            $this->filesystem->put(
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

    private function convertMethodsToString(array $methods): string
    {
        $methodsStrings = [];

        foreach ($methods as $method) {
            $methodsStrings[] = $this->replacePlaceholders(
                $this->templatesManager->getTemplate('PolicyGate.template'),
                ['{{ method }}' => $method]
            );
        }

        return implode("\n\n    ", $methodsStrings);
    }
}