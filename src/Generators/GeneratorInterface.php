<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;

interface GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void;

    public function setOptions(array $options): static;
}
