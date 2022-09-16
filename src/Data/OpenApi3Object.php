<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3PropertyTypeEnum;
use Ensi\LaravelOpenApiServerGenerator\Exceptions\RequestsEnumsNamespaceMissingException;
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

    public function getPropertiesFromObject(stdClass $object)
    {
        if (isset(get_object_vars($object)['properties'])) {
            foreach (get_object_vars($object->properties) as $propertyName => $property) {
                if (!$objectProperty = $this->properties->get($propertyName)) {
                    $objectProperty = new OpenApi3ObjectProperty(name: $propertyName, type: $property->type);
                    $this->properties->put($propertyName, $objectProperty);
                }
                $objectProperty->getPropertyFromProperty($propertyName, $property);
            }
        }
        if (isset(get_object_vars($object)['required'])) {
            foreach ($object->required as $requiredProperty) {
                if (!$objectProperty = $this->properties->get($requiredProperty)) {
                    $objectProperty = new OpenApi3ObjectProperty(
                        name: $requiredProperty,
                        type: OpenApi3PropertyTypeEnum::OBJECT->value
                    );
                    $this->properties->put($requiredProperty, $objectProperty);
                }

                $objectProperty->required = true;
            }
        }
    }

    public function toLaravelValidationRules(array $options)
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

        throw_unless(isset($options['enums_namespace']), RequestsEnumsNamespaceMissingException::class);
        $enumStrings = [];
        foreach ($enums as $enumClass => $value) {
            $enumStrings[] = 'use ' . $options['enums_namespace'] . "{$enumClass};";
        }
        if ($enumStrings) {
            $enumStrings[] = 'use Illuminate\Validation\Rules\Enum;';
        }
        $enumStrings[] = 'use Illuminate\Foundation\Http\FormRequest;';
        sort($enumStrings);
        $enumsString = implode("\n", $enumStrings);

        return [$validationsString, $enumsString];
    }
}
