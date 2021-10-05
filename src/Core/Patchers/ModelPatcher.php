<?php

namespace Ensi\LaravelOpenapiServerGenerator\Core\Patchers;

class ModelPatcher {

    /**
     * @var string
     */
    private $modelFile;

    public function __construct(string $modelFile)
    {
        $this->modelFile = $modelFile;
    }

    public function patch(): void
    {
        $model = file_get_contents($this->modelFile);

        if ($this->isClass($model)) {
            $model = $this->patchImplements($model);
            $model = $this->patchMethods($model);

            file_put_contents($this->modelFile, $model);
        }
    }

    private function isClass($model): bool
    {
        return preg_match('/^class/m', $model) > 0;
    }

    private function patchImplements($model): string
    {
        return preg_replace('/^class (\w+) implements((?:\s\w+,*)+)/m', '$0, \JsonSerializable', $model);
    }

    private function patchMethods($model): string
    {
        $model = $this->addJsonSerializeMethod($model);
        $model = $this->addFromRequestMethod($model);
        return $model;
    }

    private function addJsonSerializeMethod($model): string
    {
        return preg_replace(
            '/^}/m',
            "\n    public function jsonSerialize()\n    {\n        return ObjectSerializer::sanitizeForSerialization(\$this);\n    }\n}",
            $model
        );
    }

    private function addFromRequestMethod($model): string
    {
        return preg_replace(
            '/^}/m',
            "\n    public static function fromRequest(\\Illuminate\\Http\\Request \$request): self\n    {\n        return ObjectSerializer::deserialize(json_encode(\$request->all()), static::class);\n    }\n}",
            $model
        );
    }
}
