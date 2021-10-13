<?php

namespace Greensight\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;

interface GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject, string|array $namespaceData): void;
}
