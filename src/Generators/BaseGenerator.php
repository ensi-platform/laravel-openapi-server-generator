<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use Ensi\LaravelOpenApiServerGenerator\Data\Controllers\ControllersStorage;
use Ensi\LaravelOpenApiServerGenerator\Utils\ClassParser;
use Ensi\LaravelOpenApiServerGenerator\Utils\PhpDocGenerator;
use Ensi\LaravelOpenApiServerGenerator\Utils\PSR4PathConverter;
use Ensi\LaravelOpenApiServerGenerator\Utils\RouteHandlerParser;
use Ensi\LaravelOpenApiServerGenerator\Utils\TemplatesManager;
use Ensi\LaravelOpenApiServerGenerator\Utils\TypesMapper;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;

class BaseGenerator
{
    protected array $options = [];

    public function __construct(
        protected Filesystem $filesystem,
        protected TemplatesManager $templatesManager,
        protected PSR4PathConverter $psr4PathConverter,
        protected RouteHandlerParser $routeHandlerParser,
        protected TypesMapper $typesMapper,
        protected PhpDocGenerator $phpDocGenerator,
        protected ClassParser $classParser,
        protected ControllersStorage $controllersStorage,
    ) {
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    protected function replacePlaceholders(string $content, array $placeholders, bool $removeExcessLineBreaks = false): string
    {
        $placeholders = array_merge($placeholders, $this->formattedGlobalParams());
        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);

        // Убираем двойные переносы строк
        if ($removeExcessLineBreaks) {
            $content = preg_replace("/([\n]+){3}/", "\n\n", $content);
        }

        return $content;
    }

    protected function trimPath(string $path): string
    {
        return $path === '/' ? $path : ltrim($path, '/');
    }

    protected function getReplacedNamespace(?string $baseNamespace, string $replaceFromNamespace, string $replaceToNamespace): ?string
    {
        if ($baseNamespace) {
            return $this->replace($baseNamespace, $replaceFromNamespace, $replaceToNamespace)
                ?? throw new RuntimeException("Can't replace namespace");
        }

        return null;
    }

    protected function getReplacedClassName(?string $baseClassName, string $replaceFromClassName, string $replaceToClassName): ?string
    {
        if ($baseClassName) {
            return $this->replace($baseClassName, $replaceFromClassName, $replaceToClassName)
                ?? throw new RuntimeException("Can't replace class name");
        }

        return null;
    }

    protected function replace(string $base, string $from, string $to): ?string
    {
        if (!str_contains($base, $from)) {
            return null;
        }

        return str_replace($from, $to, $base);
    }

    protected function getNamespacedFilePath(string $fileName, ?string $namespace): string
    {
        $toDir = $this->psr4PathConverter->namespaceToPath($namespace);

        return rtrim($toDir, '/') . "/{$fileName}.php";
    }

    protected function prepareDestinationDir(string $toDir): void
    {
        if (!$toDir || $toDir === '/') {
            throw new InvalidArgumentException("Destination directory cannot be empty or /");
        }

        $this->filesystem->ensureDirectoryExists($toDir);
        $this->filesystem->cleanDirectory($toDir);
    }

    protected function putWithDirectoryCheck(string $path, string $contents): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $contents);
    }

    private function formattedGlobalParams(): array
    {
        $params = [];
        foreach ($this->options['params'] ?? [] as $key => $value) {
            $params["{{ $key }}"] = $value;
        }

        return $params;
    }

    protected function getActualClassNameAndNamespace(?string $className, ?string $namespace): array
    {
        $parseClassName = explode('/', $className);

        if (count($parseClassName) > 1) {
            if (str_contains($namespace, '\Requests')) {
                $namespace = substr($namespace, 0, strpos($namespace, '\Requests') + 9);
            } elseif (str_contains($namespace, '\Resources')) {
                $namespace = substr($namespace, 0, strpos($namespace, '\Resources') + 10);
            }

            $className = array_pop($parseClassName);
            $namespace .= '\\' . implode('\\', $parseClassName);
        }

        return [
            $className,
            $namespace,
        ];
    }
}
