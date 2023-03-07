<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use RuntimeException;

class PoliciesGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $policies = $this->extractPolicies($specObject);
        $this->createPoliciesFiles($policies, $this->templatesManager->getTemplate('Policy.template'));
    }

    // TODO: предварительная версия, необходим рефакторинг и доп. проверки
    protected function extractPolicies(SpecObjectInterface $specObject): array
    {
        $openApiData = $specObject->getSerializableData();

        $policies = [];
        $paths = $openApiData->paths ?: [];
        foreach ($paths as $routes) {
            foreach ($routes as $route) {
                if (!empty($route->{'x-lg-skip-policy-generation'})) {
                    continue;
                }

                if (empty($route->{'x-lg-handler'})) {
                    continue;
                }

                $response = $route->responses->{403} ?? null;
                if (!$response) {
                    continue;
                }

                $handler = $this->routeHandlerParser->parse($route->{'x-lg-handler'});

                try {
                    $namespace = $this->getReplacedNamespace($handler->namespace, 'Controllers', 'Policies');
                    $className = $handler->class . 'Policy';
                } catch (RuntimeException) {
                    continue;
                }

                if (empty($handler->method)) {
                    continue;
                }

                if (isset($policies["$namespace\\$className"])) {
                    $policies["$namespace\\$className"]['methods'][] = $handler->method;
                } else {
                    $methods = [$handler->method];
                    $policies["$namespace\\$className"] = compact('className', 'namespace', 'methods');
                }
            }
        }

        return $policies;
    }

    // TODO: протестировать
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
                    '{{ methods }}' => $this->convertToString($methods),
                ])
            );

            die();
        }
    }

    private function convertToString(array $methods): string
    {
        $methodsStrings = [];

        foreach ($methods as $method) {
            $methodsStrings[] = $this->replacePlaceholders(
                $this->templatesManager->getTemplate('PolicyGate.template'),
                [
                    '{{ method }}' => $method,
                ]
            );
        }

        return implode("\n\n    ", $methodsStrings);
    }
}