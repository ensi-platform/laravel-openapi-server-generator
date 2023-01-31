<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data\OpenApi3;

use Ensi\LaravelOpenApiServerGenerator\Enums\LaravelValidationRuleEnum;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyFormatEnum;
use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyTypeEnum;
use stdClass;

class OpenApi3ObjectProperty
{
    public function __construct(
        public string $type,
        public ?string $name = null,
        public ?string $format = null,
        public bool $required = false,
        public bool $nullable = false,
        public ?string $enumClass = null,
        public ?OpenApi3Object $object = null,
        public ?OpenApi3ObjectProperty $items = null,
    ) {
        //
    }

    public function fillFromStdProperty(string $propertyName, stdClass $stdProperty): void
    {
        if (std_object_has($stdProperty, 'nullable')) {
            $this->nullable = true;
        }
        if (std_object_has($stdProperty, 'format')) {
            $this->format = $stdProperty->format;
        }
        if (std_object_has($stdProperty, 'x-lg-enum-class')) {
            $this->enumClass = $stdProperty->{'x-lg-enum-class'};
        }

        if (std_object_has($stdProperty, 'type')) {
            switch (OpenApi3PropertyTypeEnum::from($stdProperty->type)) {
                case OpenApi3PropertyTypeEnum::OBJECT:
                    $this->object = new OpenApi3Object();
                    $this->object->fillFromStdObject($stdProperty);

                    break;
                case OpenApi3PropertyTypeEnum::ARRAY:
                    if (std_object_has($stdProperty, 'items')) {
                        do_with_all_of($stdProperty->items, function (stdClass $p) use ($propertyName) {
                            if (!$this->items && std_object_has($p, 'type')) {
                                $this->items = new OpenApi3ObjectProperty(type: $p->type);
                            }
                            $this->items?->fillFromStdProperty("{$propertyName}.*", $p);
                        });
                    }

                    break;
                default:
            }
        }
    }

    public function getLaravelValidationsAndEnums(array $options, array &$validations = [], array &$enums = [], string $namePrefix = null): array
    {
        $name = "{$namePrefix}{$this->name}";

        if ($this->required) {
            $validations[$name][] = "'required'";
        }
        if ($this->nullable) {
            $validations[$name][] = "'nullable'";
        }
        if ($this->enumClass) {
            $validations[$name][] = "new Enum({$this->enumClass}::class)";
            $enums[$this->enumClass] = true;
        } else {
            [$currentValidations, $currentEnums] = $this->getValidationsAndEnumsByTypeAndFormat($options, $validations, $enums, $name);
            $validations = array_merge($validations, $currentValidations);
            $enums = array_merge($enums, $currentEnums);
        }

        return [$validations, $enums];
    }

    protected function getValidationsAndEnumsByTypeAndFormat(array $options, array &$validations, array &$enums, string $name): array
    {
        $type = OpenApi3PropertyTypeEnum::from($this->type);
        $format = OpenApi3PropertyFormatEnum::tryFrom($this->format);
        switch ($type) {
            case OpenApi3PropertyTypeEnum::INTEGER:
            case OpenApi3PropertyTypeEnum::BOOLEAN:
            case OpenApi3PropertyTypeEnum::NUMBER:
                $validations[$name][] = "'{$type->toLaravelValidationRule()->value}'";

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
                        $validations[$name][] = "'{$format->toLaravelValidationRule()->value}'";

                        break;
                    case OpenApi3PropertyFormatEnum::BINARY:
                        $validations[$name][] = "'". LaravelValidationRuleEnum::FILE->value . "'";

                        break;
                    default:
                        $validations[$name][] = "'{$type->toLaravelValidationRule()->value}'";

                        break;
                }

                break;
            case OpenApi3PropertyTypeEnum::OBJECT:
                foreach ($this->object->properties ?? [] as $property) {
                    [$currentValidations, $currentEnums] = $property->getLaravelValidationsAndEnums($options, $validations, $enums, "{$name}.");
                    $validations = array_merge($validations, $currentValidations);
                    $enums = array_merge($enums, $currentEnums);
                }

                break;
            case OpenApi3PropertyTypeEnum::ARRAY:
                $validations[$name][] = "'{$type->toLaravelValidationRule()->value}'";
                [$currentValidations, $currentEnums] = $this->items->getLaravelValidationsAndEnums($options, $validations, $enums, "{$name}.*");
                $validations = array_merge($validations, $currentValidations);
                $enums = array_merge($enums, $currentEnums);
        }

        return [$validations, $enums];
    }
}
