<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data\Controllers;

class ControllersStorage
{
    /** @var array Recently created controllers */
    protected array $controllers = [];

    public function markNewControllerMethod(
        string $serversUrl,
        string $path,
        string $method,
        array $responseCodes
    ): void {
        $this->controllers[$serversUrl][$path][$method] = $responseCodes;
    }

    public function isExistControllerMethod(
        string $serversUrl,
        string $path,
        string $method,
        int $responseCode
    ): bool {
        $codes = $this->controllers[$serversUrl][$path][$method] ?? [];

        return !in_array($responseCode, $codes);
    }
}
