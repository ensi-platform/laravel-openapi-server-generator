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
    private $tempDir;

    /**
     * @var boolean
     */
    private $onlyEnumsMode;

    /**
     * @var string
     */
    private $destinationDir;

    /**
     * @var string
     */
    private $templateDir;

    public function __construct()
    {
        parent::__construct();

        $this->tempDir = config('openapi-server-generator.temp_dir', config('openapi-server-generator.output_dir'));
        $this->destinationDir = config('openapi-server-generator.destination_dir', config('openapi-server-generator.app_dir'));
        $this->templateDir = config("openapi-server-generator.template_dir", '');
        $this->onlyEnumsMode = config('openapi-server-generator.only_enums_mode', false);
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

        $this->info("Generating DTOs for file: $file, version: $version");

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

        $command = "$bin generate -i $inputFile -g php -p 'invokerPackage=$invokerPackage,modelPackage=$modelPackage' -o $this->tempDir";

        if ($this->templateDir) {
            $command .= " -t " . escapeshellarg($this->templateDir);
        }

        $this->info("Executing command: $command");

        shell_exec($command);
    }

    private function copyGeneratedDtoToApp(): void
    {
        $this->info("Clearing destination dir: " . $this->getAppPathToDto());
        
        $this->clearAppDir();

        $this->info("Copying generated DTO files to destination dir: " . $this->getAppPathToDto());
        
        $this->copyDto();

        $this->info("Removing temporary generated dir: " . $this->tempDir);

        $this->removeGeneratedDto();
    }

    private function clearAppDir(): void {
        shell_exec('rm -rf ' . $this->getAppPathToModels());
        shell_exec('mkdir -p ' . $this->getAppPathToModels());
    }

    private function copyDto(): void
    {
        if ($this->onlyEnumsMode) {
            $modelsPath = $this->getAppPathToModels();
            shell_exec("find $this->tempDir/lib/Dto -name \*Enum.php -exec cp {} $modelsPath \;");
        } else {
            shell_exec("cp -rf $this->tempDir/lib/Dto " . $this->getAppPathToDto());
            shell_exec("cp -f $this->tempDir/lib/Configuration.php " . $this->getAppPathToDto());
            shell_exec("cp -f $this->tempDir/lib/ObjectSerializer.php " . $this->getAppPathToDto());
        }
    }

    private function removeGeneratedDto(): void
    {
        shell_exec("rm -rf $this->tempDir");
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

        if (!file_exists($file)) {
            $this->info("Skipping serializer patching, no such file: $file");
            return;
        }

        $this->info("Patching serializer: $file");

        $patcher = new SerializerPatcher($file);

        $patcher->patch();
    }

    private function getVersion(): string {
        return $this->argument('version');
    }

    private function getFile() {
        return $this->argument('file');
    }

    private function replaceVersionPlaceHolderInPath(string $pathWithPossiblePlaceholder): string
    {
        $version = Str::upper($this->getVersion());

        $count = 0;
        $path = str_replace("{version}", $version, $pathWithPossiblePlaceholder, $count);
        if ($count) {
            return $path;
        }

        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $version;
    }

    private function getAppPathToDto() 
    {
        return app_path($this->replaceVersionPlaceHolderInPath($this->destinationDir));
    }

    private function getAppPathToModels()
    {
        return $this->getAppPathToDto() . DIRECTORY_SEPARATOR . self::MODEL_PACKAGE;
    }

    private function getInvokerPackage() 
    {
        return collect([
            'App',
            str_replace(DIRECTORY_SEPARATOR, '\\\\', $this->replaceVersionPlaceHolderInPath($this->destinationDir))
        ])->join('\\\\');
    }
}
