<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

use InvalidArgumentException;

class PSR4PathConverter
{
    public function __construct(private array $mappings = [])
    {
    }

    public function addMappings(array $mappings): static
    {
        foreach ($mappings as $namespace => $path) {
            $this->mappings[$namespace] =  $path;
        }

        return $this;
    }

    public function namespaceToPath(?string $namespace): string
    {
        if (is_null($namespace)) {
            return '';
        }

        foreach ($this->mappings as $mappingNamespace => $mappingPath) {
            if (str_starts_with($namespace, $mappingNamespace)) {
                $namespaceWithoutBase = substr($namespace, strlen($mappingNamespace));

                return $mappingPath . '/' . trim(str_replace("\\", '/', $namespaceWithoutBase), '/');
            }
        }

        throw new InvalidArgumentException("Namespace $namespace is unknown, supported namespaces must start with one of [". implode(array_keys($this->mappings)). "]");
    }
}
