<?php

namespace Ensi\LaravelOpenApiServerGenerator\Enums;

enum OpenApi3ContentTypeEnum: string
{
    case APPLICATION_JSON = 'application/json';
    case MULTIPART_FROM_DATA = 'multipart/form-data';
}
