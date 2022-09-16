<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyTypeEnum;
use Ensi\LaravelOpenApiServerGenerator\Exceptions\EnumsNamespaceMissingException;
use Illuminate\Support\Collection;
use stdClass;

class OpenApi3Object
{
    /** @var Collection|OpenApi3ObjectProperty[] */
    public Collection $properties;

    public function __construct()
    {
        $this->properties = collect();
    }

    public function fillFromStdObject(stdClass $object): void
    {
        if (std_object_has($object, 'properties')) {
            foreach (get_object_vars($object->properties) as $propertyName => $property) {
                /** @var OpenApi3ObjectProperty $objectProperty */
                $objectProperty = $this->properties->get($propertyName);
                if (!$objectProperty) {
                    $objectProperty = new OpenApi3ObjectProperty(type: $property->type, name: $propertyName, );
                    $this->properties->put($propertyName, $objectProperty);
                }
                $objectProperty->fillFromStdProperty($propertyName, $property);
            }
        }
        if (std_object_has($object, 'required')) {
            foreach ($object->required as $requiredProperty) {
                $objectProperty = $this->properties->get($requiredProperty);
                if (!$objectProperty) {
                    $objectProperty = new OpenApi3ObjectProperty(
                        type: OpenApi3PropertyTypeEnum::OBJECT->value,
                        name: $requiredProperty,
                    );
                    $this->properties->put($requiredProperty, $objectProperty);
                }

                $objectProperty->required = true;
            }
        }
    }

    public function toLaravelValidationRules(array $options): array
    {
        $validations = [];
        $enums = [];
        foreach ($this->properties as $property) {
            [$propertyValidations, $propertyEnums] = $property->getLaravelValidationsAndEnums($options);

            $validations = array_merge($propertyValidations, $validations);
            $enums = array_merge($propertyEnums, $enums);
        }

        $validationStrings = [];
        foreach ($validations as $propertyName => $validation) {
            $validationString = implode(', ', $validation);
            $validationStrings[] = "'{$propertyName}' => [{$validationString}],";
        }
        $validationsString = implode("\n            ", $validationStrings);

        $enumStrings = [];
        if ($enums) {
            throw_unless(isset($options['enums']['namespace']), EnumsNamespaceMissingException::class);

            foreach ($enums as $enumClass => $value) {
                $enumStrings[] = 'use ' . $options['enums']['namespace'] . "{$enumClass};";
            }
            if ($enumStrings) {
                $enumStrings[] = 'use Illuminate\Validation\Rules\Enum;';
            }
            $enumStrings[] = 'use Illuminate\Foundation\Http\FormRequest;';
            sort($enumStrings);
            $enumsString = implode("\n", $enumStrings);
        }

        return [$validationsString, $enumsString];
    }
}
