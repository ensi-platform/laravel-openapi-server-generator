<?php

namespace Greensight\LaravelOpenapiServerGenerator\Core\Patchers;

use Illuminate\Support\Str;

class EnumPatcher {

    /**
     * @var string
     */
    private $enumFile;

    /**
     * @var string
     */
    private $apidocDir;

    public function __construct(string $enumFile, string $apidocDir)
    {
        $this->enumFile = $enumFile;
        $this->apidocDir = $apidocDir;
    }

    public function patch(): void {
        $enumName = basename($this->enumFile, '.php');
        $spec = "$this->apidocDir/components/" . $this->toSnakeCase($enumName) . '.yaml';

        preg_match_all(
            '/\s-\s(?<value>[\d]+)\s#\s(?<name>[\w]+)\s\|\s(?<title>.+)/mu',
            file_get_contents($spec),
            $constants,
            PREG_SET_ORDER
        );

        $enum = file_get_contents($this->enumFile);

        if ($constants !== null) {

            foreach ($constants as $constant) {
                $enum = $this->patchConstantProperties(
                    $enum,
                    $constant['value'],
                    Str::upper($constant['name']),
                    $constant['title']
                );
            }
        }

        $enum = $this->patchNamespace($enum);

        file_put_contents($this->enumFile, $enum);
    }

    private function patchConstantProperties(string $enum, string $value, string $name, string $title): string
    {
        $enum = preg_replace(
            '/' . "const $value = $value;" .'/m',
            "public const $name = $value; // $title",
            $enum
        );

        $enum = preg_replace(
            '/' . "self::$value" .  '/m',
            "self::$name",
            $enum
        );

        return $enum;
    }

    private function patchNamespace(string $enum): string
    {
        return preg_replace(
            '/^namespace ((?:[\w]+\\\)+)([\w]+)?/m',
            'namespace $1Enums;',
            $enum
        );
    }

    private function toSnakeCase(string $str)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
    }
}
