<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\ModelPatcher;
use Greensight\LaravelOpenapiServerGenerator\Core\Patchers\SerializerPatcher;

class GenerateServerVersion extends Command {

    const MODEL_PACKAGE = 'Dto';

    /**
     * @var string
     */
    protected $signature = 'openapi:generate-server-version
                            {file : Path to root file with openapi spec}
                            {version=v1 : String version for server dto: v1, v2, etc.}';

    /**
     * @var string
     */
    protected $description = 'Generate server from openapi spec files by OpenApi Generator with specific version';

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
        $file = $this->getFile();
        $version = $this->getVersion();

        $this->info("Generate Dto for file: $file, version: $version");

        $this->generateDto();
        $this->copyGeneratedDtoToApp();
        $this->patchModels();
        $this->patchSerializer();
    }

    private function generateDto(): void
    {
        $modelPackage = self::MODEL_PACKAGE;
        $bin = 'npx @openapitools/openapi-generator-cli';

        $inputFile = $this->getFile();
        $invokerPackage = $this->getInvokerPackage();

        $command = "$bin generate -i $inputFile -g php -p 'invokerPackage=$invokerPackage,modelPackage=$modelPackage' -o $this->outputDir";

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
        shell_exec("cp -rf $this->outputDir/lib/Dto " . $this->getAppPathToDto());
        shell_exec("cp -f $this->outputDir/lib/Configuration.php " . $this->getAppPathToDto());
        shell_exec("cp -n $this->outputDir/lib/ObjectSerializer.php " . $this->getAppPathToDto());
    }

    private function removeGeneratedDto(): void
    {
        shell_exec("rm -rf $this->outputDir");
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

    private function getVersion(): string {
        return $this->argument('version');
    }

    private function getFile() {
        return $this->argument('file');
    }

    private function getAppPathToDto() {
        return app_path($this->appDir . DIRECTORY_SEPARATOR . Str::upper($this->getVersion()));
    }

    private function getAppPathToModels()
    {
        return $this->getAppPathToDto() . DIRECTORY_SEPARATOR . self::MODEL_PACKAGE;
    }

    private function getInvokerPackage() {
        return collect([
            'App',
            str_replace(DIRECTORY_SEPARATOR, '\\\\', $this->appDir),
            Str::upper($this->getVersion())
        ])->join('\\\\');
    }
}
