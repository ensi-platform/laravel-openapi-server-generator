<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\LaravelValidationRuleEnum;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyFormatEnum;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyTypeEnum;
use Ensi\LaravelOpenApiServerGenerator\Generators\BaseGenerator;

class ObjectProperty
{
    public function __construct(
        public string|null $name = null,
        public string|null $type = null,
        public string|null $format = null,
        public bool|null $required = null,
        public bool|null $nullable = null,
        public string|null $enumClass = null,
    ) {
    }

    public function toLaravelValidations(): array
    {
        $validations = [];
        $usesEnum = null;
        if ($this->required) {
            $validations[] = "'required'";
        }
        if ($this->nullable) {
            $validations[] = "'nullable'";
        }
        if ($this->enumClass) {
            $validations[] = "new Enum({$this->enumClass}::class)";
            $usesEnum = 'use ' . BaseGenerator::ENUMS_NAMESPACE . "\\{$this->enumClass};";
        } else {
            $validations = array_merge(
                $validations,
                array_map(
                    fn (LaravelValidationRuleEnum $validationRule) => "'{$validationRule->value}'",
                    $this->getValidationsByTypeAndFormat($this->type, $this->format)
                )
            );
        }
        $validationsString = implode(', ', $validations);
        $laravelValidationRules = "'{$this->name}' => [{$validationsString}],";

        return [$laravelValidationRules, $usesEnum];
    }

    protected function getValidationsByTypeAndFormat(string $type, string|null $format = null): array
    {
        $type = OpenApi3PropertyTypeEnum::from($type);
        $format = OpenApi3PropertyFormatEnum::tryFrom($format);
        $validations = [];
        switch ($type) {
            case OpenApi3PropertyTypeEnum::INTEGER:
            case OpenApi3PropertyTypeEnum::BOOLEAN:
            case OpenApi3PropertyTypeEnum::NUMBER:
                $validations[] = $type->toLaravelValidationRule();
                break;
            case OpenApi3PropertyTypeEnum::STRING:
                switch ($format) {
                    case OpenApi3PropertyFormatEnum::DATE:
                    case OpenApi3PropertyFormatEnum::DATE_TIME:
                    case OpenApi3PropertyFormatEnum::PASSWORD:
                    case OpenApi3PropertyFormatEnum::EMAIL:
                    case OpenApi3PropertyFormatEnum::IPV4:
                    case OpenApi3PropertyFormatEnum::IPV6:
                    case OpenApi3PropertyFormatEnum::TIMEZONE:
                    case OpenApi3PropertyFormatEnum::PHONE:
                    case OpenApi3PropertyFormatEnum::URL:
                    case OpenApi3PropertyFormatEnum::UUID:
                        $validations[] = $format->toLaravelValidationRule();
                        break;
                    case OpenApi3PropertyFormatEnum::BINARY:
                        $validations[] = LaravelValidationRuleEnum::FILE;
                        break;
                    default:
                        $validations[] = $type->toLaravelValidationRule();
                        break;
                }
                break;
            case OpenApi3PropertyTypeEnum::ARRAY:
                $validations[] = $type->toLaravelValidationRule();
        }

        return $validations;
    }
}
