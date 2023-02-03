<?php

namespace Ensi\LaravelOpenApiServerGenerator\Enums;

enum LaravelValidationRuleEnum: string
{
    // types
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case NUMERIC = 'numeric';
    case ARRAY = 'array';
    case FILE = 'file';

    // formats
    case DATE = 'date';
    case DATE_TIME = 'date_format:Y-m-d\TH:i:s.u\Z';
    case PASSWORD = 'password';
    case EMAIL = 'email';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case TIMEZONE = 'timezone';
    case PHONE = 'regex:/^\+7\d{10}$/';
    case URL = 'url';
    case UUID = 'uuid';
}
