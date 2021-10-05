<?php

namespace Ensi\LaravelOpenapiServerGenerator\Core\Patchers;

class SerializerPatcher {

    /**
     * @var string
     */
    private $serializerFile;

    public function __construct(string $serializerFile)
    {
        $this->serializerFile = $serializerFile;
    }

    public function patch(): void
    {
        $serializer = file_get_contents($this->serializerFile);

        $serializer = $this->patchDateTimeFormat($serializer);

        file_put_contents($this->serializerFile, $serializer);
    }

    private function patchDateTimeFormat(string $serializer): string
    {
        return preg_replace(
            '/(private static \$dateTimeFormat) = (?:.*)/',
            '$1 = \'Y-m-d H:i:s\';',
            $serializer
        );
    }
}
