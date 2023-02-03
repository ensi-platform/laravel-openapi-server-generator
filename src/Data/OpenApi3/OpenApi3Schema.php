<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data\OpenApi3;

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
                do_with_all_of($schema, function (stdClass $p) {
                    $this->object->fillFromStdObject($p);
                });

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
