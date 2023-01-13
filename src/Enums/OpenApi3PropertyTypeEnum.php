<?php

namespace Ensi\LaravelOpenApiServerGenerator\Enums;

enum OpenApi3PropertyTypeEnum: string
{
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
    case ARRAY = 'array';
    case OBJECT = 'object';

    public function toLaravelValidationRule(): LaravelValidationRuleEnum
    {
        return match ($this) {
            OpenApi3PropertyTypeEnum::INTEGER   =>  LaravelValidationRuleEnum::INTEGER,
            OpenApi3PropertyTypeEnum::STRING    =>  LaravelValidationRuleEnum::STRING,
            OpenApi3PropertyTypeEnum::BOOLEAN   =>  LaravelValidationRuleEnum::BOOLEAN,
            OpenApi3PropertyTypeEnum::NUMBER    =>  LaravelValidationRuleEnum::NUMERIC,
            OpenApi3PropertyTypeEnum::ARRAY     =>  LaravelValidationRuleEnum::ARRAY,
        };
    }
}
