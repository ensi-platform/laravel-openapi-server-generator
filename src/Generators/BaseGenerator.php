<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

use Ensi\LaravelOpenApiServerGenerator\Utils\PSR4PathConverter;
use Ensi\LaravelOpenApiServerGenerator\Utils\RouteHandlerParser;
use Ensi\LaravelOpenApiServerGenerator\Utils\TemplatesManager;
use Ensi\LaravelOpenApiServerGenerator\Utils\TypesMapper;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class BaseGenerator
{
    protected array $options = [];

    public function __construct(
        protected Filesystem $filesystem,
        protected TemplatesManager $templatesManager,
        protected PSR4PathConverter $psr4PathConverter,
        protected RouteHandlerParser $routeHandlerParser,
        protected TypesMapper $typesMapper,
    ) {
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    protected function replacePlaceholders(string $content, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    protected function trimPath(string $path): string
    {
        return $path === '/' ? $path : ltrim($path, '/');
    }

    protected function getNamespacedFilePath(string $fileName, string $namespace, ): string
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
}
