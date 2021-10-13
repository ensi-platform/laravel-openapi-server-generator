<?php

namespace Greensight\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;
use LogicException;
use stdClass;

class EnumsGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject, string|array $namespaceData): void
    {
        if (!is_string($namespaceData)) {
            throw new InvalidArgumentException("EnumsGenerator supports only strings as namespaceData");
        }

        $namespace = rtrim($namespaceData, "\\");
        $toDir = $this->psr4PathConverter->namespaceToPath($namespace);

        $this->prepareDestinationDir($toDir);

        $openApiData = $specObject->getSerializableData();
        $enums = $this->extractEnums($openApiData);

        $template = $this->templatesManager->getTemplate('Enum.template');
        foreach ($enums as $className => $schema) {
            $enumType = get_debug_type($schema->enum[0]);
            $this->filesystem->put(
                rtrim($toDir, '/') . "/{$className}.php",
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ className }}' => $className,
                    '{{ constants }}' => $this->convertEnumSchemaToConstants($schema),
                    '{{ enumType }}' => $enumType,
                    '{{ valuesArray }}' => $this->convertEnumSchemaToValuesArray($schema),
                ])
            );
        }
    }

    private function extractEnums(stdClass  $openApiData): array
    {
        $schemas = (array) $openApiData?->components?->schemas;

        return array_filter($schemas, fn ($schema) => !empty($schema->enum));
    }

    private function convertEnumSchemaToConstants(stdClass $schema): string
    {
        $result = '';
        foreach ($schema->enum as $i => $enum) {
            $varName = $schema->{'x-enum-varnames'}[$i] ?? null;
            if ($varName === null) {
                throw new LogicException("x-enum-varnames for enum \"{$enum}\" is not set");
            }
            $value = var_export($enum, true);
            $result .= "    public const {$varName} = {$value};\n";
        }

        return $result;
    }

    private function convertEnumSchemaToValuesArray(stdClass $schema): string
    {
        $result = "[\n";
        foreach ($schema->{'x-enum-varnames'} as $varName) {
            $result .= "            self::{$varName},\n";
        }
        $result .= '        ]';

        return $result;
    }
}
