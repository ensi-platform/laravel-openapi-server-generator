<?php

namespace Ensi\LaravelOpenApiServerGenerator\Generators;

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

    protected function getReplacedNamespace(?string $baseNamespace, string $replaceFromNamespace, string $replaceToNamespace): ?string
    {
        if ($baseNamespace) {
            if (!str_contains($baseNamespace, $replaceFromNamespace)) {
                throw new RuntimeException("Can't replace namespace");
            }

            return str_replace($replaceFromNamespace, $replaceToNamespace, $baseNamespace);
        }

        return null;
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

    protected function formatNamespaces(array $namespaces): string
    {
        $namespaces = array_unique($namespaces);
        sort($namespaces);

        return implode("\n", array_map(fn (string $namespaces) => "use {$namespaces};", $namespaces));
    }
}
