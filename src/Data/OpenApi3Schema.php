<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Ensi\LaravelOpenApiServerGenerator\Enums\OpenApi3ContentTypeEnum;
use stdClass;

abstract class OpenApi3Schema
{
    public OpenApi3ContentTypeEnum $contentType;
    public OpenApi3Object $object;

    public function __construct()
    {
        $this->object = new OpenApi3Object();
    }

    protected function getObjectFromSchema(OpenApi3ContentTypeEnum $contentType, stdClass $requestBody)
    {
        switch ($contentType) {
            case OpenApi3ContentTypeEnum::APPLICATION_JSON:
                $schema = $requestBody->content->{OpenApi3ContentTypeEnum::APPLICATION_JSON->value}->schema;
                if (isset(get_object_vars($schema)['allOf'])) {
                    foreach ($schema->allOf as $object) {
                        $this->object->getPropertiesFromObject($object);
                    }
                } else {
                    $this->object->getPropertiesFromObject($schema);
                }

                break;
            case OpenApi3ContentTypeEnum::MULTIPART_FROM_DATA:
                $this->object->getPropertiesFromObject(
                    $requestBody->content
                        ->{OpenApi3ContentTypeEnum::MULTIPART_FROM_DATA->value}
                        ->schema
                );

                break;
        }
    }
}
