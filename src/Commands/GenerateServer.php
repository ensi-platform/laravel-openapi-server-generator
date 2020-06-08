<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use Illuminate\Console\Command;

class GenerateServer extends Command {

    /**
     * @var string
     */
    protected $signature = 'openapi:generate-server';

    /**
     * @var string
     */
    protected $description = 'Generate server by from openapi spec files by OpenApi Generator';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->generateDto();
        $this->copyDtoToApp();
        $this->patchEnums();
        $this->patchModels();
    }

    private function generateDto(): void
    {
        $outputDir = config('openapi-server-generator.output_dir');
        shell_exec("./node_modules/.bin/openapi-generator generate -i public/api-docs/index.yaml -g php -p 'invokerPackage=App\\\\OpenApiGenerated,modelPackage=Dto' -o $outputDir");
    }

    private function copyDtoToApp(): void
    {
        $outputDir = config('openapi-server-generator.output_dir');
        $appDir = config('openapi-server-generator.app_dir');

        shell_exec("rm -rf $appDir");
        shell_exec("mkdir -p $appDir");
        shell_exec("cp -rf $outputDir/lib/Dto $appDir");
        shell_exec("cp -f $outputDir/lib/Configuration.php $appDir");
        shell_exec("cp -f $outputDir/lib/ObjectSerializer.php $appDir");
    }

    private function patchEnums(): void
    {
        $appDir = config('openapi-server-generator.app_dir');

        shell_exec("mkdir -p $appDir/Enums");

        foreach (glob("$appDir/Dto/*Enum.php") as $file) {
            echo "\nEnum: $file";

            $fileName = basename($file);
            rename($file, "$appDir/Enums/$fileName");
        }
    }

    private function patchModels(): void
    {
        $appDir = $appDir = config('openapi-server-generator.app_dir');

        foreach (glob("$appDir/Dto/*.php") as $file) {
            $content = file_get_contents($file);

            if (preg_match('/^class/m', $content) > 0) {
                echo "\nModel: $file";

                $content = preg_replace('/^}/m', "\n    public function jsonSerialize()\n    {\n        return ObjectSerializer::sanitizeForSerialization(\$this);\n    }\n}", $content);
                $content = preg_replace('/^}/m', "\n    public static function fromRequest(\\Illuminate\\Http\\Request \$request): self\n    {\n        return ObjectSerializer::deserialize(json_encode(\$request->all()), static::class);\n    }\n}", $content);

                file_put_contents($file, $content);
            }
        }
    }
}
