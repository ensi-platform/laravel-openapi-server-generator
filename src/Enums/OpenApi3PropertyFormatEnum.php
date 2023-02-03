<?php

namespace Ensi\LaravelOpenApiServerGenerator\Enums;

enum OpenApi3PropertyFormatEnum: string
{
    // build-in
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case PASSWORD = 'password';
    case BYTE = 'byte';
    case BINARY = 'binary';

    // custom
    case EMAIL = 'email';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case TIMEZONE = 'timezone';
    case PHONE = 'phone';
    case URL = 'url';
    case UUID = 'uuid';

    public function toLaravelValidationRule(): LaravelValidationRuleEnum
    {
        return match ($this) {
            OpenApi3PropertyFormatEnum::DATE => LaravelValidationRuleEnum::DATE,
            OpenApi3PropertyFormatEnum::DATE_TIME => LaravelValidationRuleEnum::DATE_TIME,
            OpenApi3PropertyFormatEnum::PASSWORD => LaravelValidationRuleEnum::PASSWORD,
            OpenApi3PropertyFormatEnum::BINARY => LaravelValidationRuleEnum::FILE,
            OpenApi3PropertyFormatEnum::EMAIL => LaravelValidationRuleEnum::EMAIL,
            OpenApi3PropertyFormatEnum::IPV4 => LaravelValidationRuleEnum::IPV4,
            OpenApi3PropertyFormatEnum::IPV6 => LaravelValidationRuleEnum::IPV6,
            OpenApi3PropertyFormatEnum::TIMEZONE => LaravelValidationRuleEnum::TIMEZONE,
            OpenApi3PropertyFormatEnum::PHONE => LaravelValidationRuleEnum::PHONE,
            OpenApi3PropertyFormatEnum::URL => LaravelValidationRuleEnum::URL,
            OpenApi3PropertyFormatEnum::UUID => LaravelValidationRuleEnum::UUID,
        };
    }
}
