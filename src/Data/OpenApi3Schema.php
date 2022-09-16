<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3ContentTypeEnum;
use stdClass;

class OpenApi3Schema
{
    public OpenApi3ContentTypeEnum $contentType;
    public OpenApi3Object $object;

    public function __construct()
    {
        $this->object = new OpenApi3Object();
    }

    public function fillFromStdRequestBody(OpenApi3ContentTypeEnum $contentType, stdClass $requestBody): void
    {
        switch ($contentType) {
            case OpenApi3ContentTypeEnum::APPLICATION_JSON:
                $schema = $requestBody->content->{OpenApi3ContentTypeEnum::APPLICATION_JSON->value}->schema;
                if (std_object_has($schema, 'allOf')) {
                    foreach ($schema->allOf as $object) {
                        $this->object->fillFromStdObject($object);
                    }
                } else {
                    $this->object->fillFromStdObject($schema);
                }

                break;
            case OpenApi3ContentTypeEnum::MULTIPART_FROM_DATA:
                $this->object->fillFromStdObject(
                    $requestBody->content
                        ->{OpenApi3ContentTypeEnum::MULTIPART_FROM_DATA->value}
                        ->schema
                );

                break;
        }
    }
}
