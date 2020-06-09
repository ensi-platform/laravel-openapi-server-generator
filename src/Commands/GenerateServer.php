<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\EnumPatcher;
use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\ModelPatcher;
use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\SerializerPatcher;

class GenerateServer extends Command {

    const MODEL_PACKAGE = 'Dto';
    const ENUM_PACKAGE = 'Enums';

    /**
     * @var string
     */
    protected $signature = 'openapi:generate-server';

    /**
     * @var string
     */
    protected $description = 'Generate server from openapi spec files by OpenApi Generator';

    /**
     * @var string
     */
    private $outputDir;

    /**
     * @var string
     */
    private $appDir;

    public function __construct()
    {
        parent::__construct();

        $this->outputDir = config('openapi-server-generator.output_dir');
        $this->appDir = config('openapi-server-generator.app_dir');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->generateDto();
        $this->copyGeneratedDtoToApp();
        $this->patchEnums();
        $this->patchModels();
        $this->patchSerializer();
    }

    private function generateDto(): void
    {
        $apidocDir = config('openapi-server-generator.apidoc_dir');
        $bin = config('openapi-server-generator.openapi_generator_bin');

        $invokerPackage = 'App\\\\' . str_replace(DIRECTORY_SEPARATOR, '\\\\', $this->appDir);
        $modelPackage = self::MODEL_PACKAGE;

        shell_exec(
            "$bin generate -i $apidocDir/index.yaml -g php -p 'invokerPackage=$invokerPackage,modelPackage=$modelPackage' -o $this->outputDir"
        );
    }

    private function copyGeneratedDtoToApp(): void
    {
        $this->clearAppDir();

        Log::info('Clear app dir: ' . $this->getAppPathToDto());

        $this->copyDto();

        Log::info('Copy generated dto files to app dir: ' . $this->getAppPathToDto());

        $this->removeGeneratedDto();

        Log::info('Remote generated dto dir: ' . $this->outputDir);
    }

    private function clearAppDir(): void {
        shell_exec('rm -rf ' . implode(' ', [ $this->getAppPathToModels(), $this->getAppPathToEnums() ]));
        shell_exec('mkdir -p ' . implode(' ', [ $this->getAppPathToModels(), $this->getAppPathToEnums() ]));
    }

    private function copyDto(): void
    {
        shell_exec("cp -rf $this->outputDir/lib/Dto " . app_path($this->appDir));
        shell_exec("cp -f $this->outputDir/lib/Configuration.php " . app_path($this->appDir));
        shell_exec("cp -n $this->outputDir/lib/ObjectSerializer.php " . app_path($this->appDir));
    }

    private function removeGeneratedDto(): void
    {
        shell_exec("rm -rf $this->outputDir");
    }

    private function patchEnums(): void
    {
        $apidocDir = config('openapi-server-generator.apidoc_dir');

        foreach (glob($this->getAppPathToDto() . '/Dto/*Enum.php') as $file) {
            Log::info("Patch enum: $file");

            $patcher = new EnumPatcher($file, $apidocDir);

            $patcher->patch();

            rename(
                $file,
                $this->getAppPathToEnums() . DIRECTORY_SEPARATOR . basename($file)
            );
        }
    }

    private function patchModels(): void
    {
        foreach (glob($this->getAppPathToDto() . '/Dto/*.php') as $file) {
            Log::info("Patch model: $file");

            $patcher = new ModelPatcher($file);

            $patcher->patch();
        }
    }

    private function patchSerializer(): void
    {
        $file = $this->getAppPathToDto() . DIRECTORY_SEPARATOR . 'ObjectSerializer.php';

        Log::info("Patch serializer: $file");

        $patcher = new SerializerPatcher($file);

        $patcher->patch();
    }

    private function getAppPathToDto() {
        return app_path($this->appDir);
    }

    private function getAppPathToModels()
    {
        return $this->getAppPathToDto() . DIRECTORY_SEPARATOR . self::MODEL_PACKAGE;
    }

    private function getAppPathToEnums()
    {
        return $this->getAppPathToDto() . DIRECTORY_SEPARATOR . self::ENUM_PACKAGE;
    }
}
