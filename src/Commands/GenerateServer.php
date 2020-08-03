<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use Illuminate\Console\Command;

use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\EnumPatcher;
use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\ModelPatcher;
use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\SerializerPatcher;

class GenerateServer extends Command {

    const MODEL_PACKAGE = 'Dto';

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

    /**
     * @var string
     */
    private $apidocDir;

    public function __construct()
    {
        parent::__construct();

        $this->outputDir = config('openapi-server-generator.output_dir');
        $this->appDir = config('openapi-server-generator.app_dir');
        $this->apidocDir = config('openapi-server-generator.apidoc_dir');
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
        $invokerPackage = 'App\\\\' . str_replace(DIRECTORY_SEPARATOR, '\\\\', $this->appDir);
        $modelPackage = self::MODEL_PACKAGE;
        $bin = 'npx @openapitools/openapi-generator-cli';

        $command = "$bin generate -i $this->apidocDir/index.yaml -g php -p 'invokerPackage=$invokerPackage,modelPackage=$modelPackage' -o $this->outputDir";

        $this->info("Execute command: $command");

        shell_exec($command);
    }

    private function copyGeneratedDtoToApp(): void
    {
        $this->clearAppDir();

        $this->info("Clear app dir: " . $this->getAppPathToDto());

        $this->copyDto();

        $this->info("Copy generated dto files to app dir: " . $this->getAppPathToDto());

        $this->removeGeneratedDto();

        $this->info("Remove generated dto dir: " . $this->outputDir);
    }

    private function clearAppDir(): void {
        shell_exec('rm -rf ' . $this->getAppPathToModels());
        shell_exec('mkdir -p ' . $this->getAppPathToModels());
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
        $enums = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->getAppPathToModels(),
                    FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                )
            ),
            '/Enum\.php$/i',
            RegexIterator::MATCH
        );

        foreach ($enums as $enum) {
            $this->info("Patch enum: $enum");

            $patcher = new EnumPatcher($enum, $this->apidocDir);

            $patcher->patch();
        }
    }

    private function patchModels(): void
    {
        $models = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->getAppPathToModels(),
                    FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                )
            ),
            '/(?<!Enum)\.php$/i',
            RegexIterator::MATCH
        );

        foreach ($models as $model) {
            $this->info("Patch model: $model");

            $patcher = new ModelPatcher($model);

            $patcher->patch();
        }
    }

    private function patchSerializer(): void
    {
        $file = $this->getAppPathToDto() . DIRECTORY_SEPARATOR . 'ObjectSerializer.php';

        $this->info("Patch serializer: $file");

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
}
