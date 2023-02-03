<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use cebe\openapi\SpecObjectInterface;
use InvalidArgumentException;
use LogicException;
use stdClass;

class EnumsGenerator extends BaseGenerator implements GeneratorInterface
{
    public function generate(SpecObjectInterface $specObject): void
    {
        $namespaceData = $this->options['enums']['namespace'] ?? null;
        if (!is_string($namespaceData)) {
            throw new InvalidArgumentException("EnumsGenerator must be configured with string as 'namespace'");
        }

        $namespace = rtrim($namespaceData, "\\");
        $toDir = $this->psr4PathConverter->namespaceToPath($namespace);

        $this->prepareDestinationDir($toDir);

        $openApiData = $specObject->getSerializableData();
        $enums = $this->extractEnums($openApiData);

        $template = $this->templatesManager->getTemplate('Enum.template');
        foreach ($enums as $enumName => $schema) {
            $enumType = $this->getEnumType($schema, $enumName);
            $this->filesystem->put(
                rtrim($toDir, '/') . "/{$enumName}.php",
                $this->replacePlaceholders($template, [
                    '{{ namespace }}' => $namespace,
                    '{{ enumName }}' => $enumName,
                    '{{ cases }}' => $this->convertEnumSchemaToCases($schema),
                    '{{ enumType }}' => $enumType,
                    '{{ enumPhpDoc }}' => $this->convertEnumSchemaToPhpDoc($schema),
                ])
            );
        }
    }

    private function extractEnums(stdClass  $openApiData): array
    {
        $schemas = (array) $openApiData?->components?->schemas;

        return array_filter($schemas, fn ($schema) => !empty($schema->enum));
    }

    private function getEnumType(stdClass $schema, string $enumName): string
    {
        return match ($schema->type) {
            "integer" => "int",
            "string" => "string",
            default => throw new LogicException("Enum {$enumName} has invalid type '{$schema->type}'. Supported types are: ['integer', 'string']"),
        };
    }

    private function convertEnumSchemaToCases(stdClass $schema): string
    {
        $result = '';
        foreach ($schema->enum as $i => $enum) {
            $varName = $schema->{'x-enum-varnames'}[$i] ?? null;
            if ($varName === null) {
                throw new LogicException("x-enum-varnames for enum \"{$enum}\" is not set");
            }
            $description = $schema->{'x-enum-descriptions'}[$i] ?? null;
            if ($description) {
                $result .= "    /** {$description} */\n";
            }
            $value = var_export($enum, true);
            $result .= "    case {$varName} = {$value};\n";
        }

        return rtrim($result, "\n");
    }

    private function convertEnumSchemaToPhpDoc(stdClass $schema): string
    {
        return $schema->description
        ? "\n" . $this->phpDocGenerator->fromText(text: $schema->description, deleteEmptyLines: true)
        : "\n";
    }
}
