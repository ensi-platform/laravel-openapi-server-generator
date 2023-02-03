<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data\OpenApi3;

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
                    do_with_all_of($property, function (stdClass $p) use (&$objectProperty, $propertyName) {
                        if (!$objectProperty && std_object_has($p, 'type')) {
                            $objectProperty = new OpenApi3ObjectProperty(type: $p->type, name: $propertyName);
                        }
                    });
                    if (!$objectProperty) {
                        continue;
                    }
                    $this->properties->put($propertyName, $objectProperty);
                }
                do_with_all_of($property, function (stdClass $p) use ($objectProperty, $propertyName) {
                    $objectProperty->fillFromStdProperty($propertyName, $p);
                });
            }
        }
        if (std_object_has($object, 'required') && is_array($object->required)) {
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

        if ($validations) {
            $validationStrings = [];
            foreach ($validations as $propertyName => $validation) {
                $validationString = implode(', ', $validation);
                $validationStrings[] = "'{$propertyName}' => [{$validationString}],";
            }
            $validationsString = implode("\n            ", $validationStrings);
        } else {
            $validationsString = '';
        }

        if ($enums) {
            throw_unless(isset($options['enums']['namespace']), EnumsNamespaceMissingException::class);

            $enumStrings = [];
            foreach ($enums as $enumClass => $value) {
                $enumStrings[] = 'use ' . $options['enums']['namespace'] . "{$enumClass};";
            }
            if ($enumStrings) {
                $enumStrings[] = 'use Illuminate\Validation\Rules\Enum;';
            }
            sort($enumStrings);
            $enumsString = implode("\n", $enumStrings);
        } else {
            $enumsString = '';
        }

        return [$validationsString, $enumsString];
    }
}
