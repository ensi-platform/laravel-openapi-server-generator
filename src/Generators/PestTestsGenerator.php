<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

class PestTestsGenerator extends TestsGenerator
{
    protected function convertRoutesToImportsString(array $routes): string
    {
        $importsArray = [];
        foreach ($routes as ['method' => $httpMethod, 'responseContentType' => $responseContentType]) {
            $newImport = "use function Pest\Laravel\\" . $this->getPhpHttpTestMethod($httpMethod, $responseContentType) . ";";
            $importsArray[$newImport] = $newImport;
        }

        sort($importsArray);

        return implode("\n", $importsArray);
    }

    private function getPhpHttpTestMethod(string $httpMethod, string $responseContentType): string
    {
        return $responseContentType === 'application/json'
            ? $this->getPhpHttpTestMethodJson($httpMethod)
            : $this->getPhpHttpTestMethodCommon($httpMethod);
    }

    private function getPhpHttpTestMethodJson(string $httpMethod): string
    {
        return $httpMethod . 'Json';
    }

    private function getPhpHttpTestMethodCommon(string $httpMethod): string
    {
        return $httpMethod;
    }

    protected function convertRoutesToTestsString(array $routes, string $serversUrl, bool $onlyNewMethods = false): string
    {
        $testsFunctions = $onlyNewMethods ? [] : ["uses()->group('component');"];

        foreach ($routes as $route) {
            foreach ($route['responseCodes'] as $responseCode) {
                if ($responseCode < 200 || $responseCode >= 500) {
                    continue;
                }

                $methodExists = $this->controllersStorage->isExistControllerMethod(
                    serversUrl: $serversUrl,
                    path: $route['path'],
                    method: $route['method'],
                    responseCode: $responseCode,
                );

                if ($onlyNewMethods && $methodExists) {
                    continue;
                }

                $url = $serversUrl . $route['path'];
                $testName = strtoupper($route['method']) . ' ' . $url. ' ' .  $responseCode;
                $phpHttpMethod = $this->getPhpHttpTestMethod($route['method'], $route['responseContentType']);
                $testsFunctions[] = <<<FUNC

                test('{$testName}', function () {
                    $phpHttpMethod('{$url}')
                        ->assertStatus({$responseCode});
                });
                FUNC;
            }
        }

        return implode("\n", $testsFunctions);
    }

    protected function getTemplateName(): string
    {
        return "PestTest.template";
    }
}
