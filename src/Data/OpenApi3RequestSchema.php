<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3ContentTypeEnum;
use stdClass;

class OpenApi3RequestSchema extends OpenApi3Schema
{
    public static function fromSplClass(OpenApi3ContentTypeEnum $contentType, stdClass $schema)
    {
        $openApiSchema = new self();

        $openApiSchema->getObjectFromSchema($contentType, $schema);

        return $openApiSchema;
    }
}
