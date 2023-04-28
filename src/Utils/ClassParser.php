<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use ReflectionClass;

class ClassParser
{
    public const NAMESPACE_LINE_PATTERN = '/^use (.*);$/';

    public ReflectionClass $ref;

    protected ?Collection $methods = null;

    public function __construct(
        protected Filesystem $filesystem,
    ) {
    }

    public function parse(string $className): self
    {
        $this->ref = new ReflectionClass($className);
        $this->methods = null;

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->getMethods()->isEmpty();
    }

    public function getMethods(): Collection
    {
        if (!$this->methods) {
            $this->methods = collect($this->ref->getMethods())->keyBy('name');
        }

        return $this->methods;
    }

    public function hasMethod(string $methodName): bool
    {
        return $this->getMethods()->has($methodName);
    }

    public function getStartLine(bool $withoutComments = false): int
    {
        $comments = $this->ref->getDocComment();
        if ($withoutComments || !$comments) {
            return $this->ref->getStartLine();
        }

        return $this->ref->getStartLine() - count(explode("\n", $comments));
    }

    public function getEndLine(): int
    {
        return $this->ref->getEndLine();
    }

    public function getFileName(): string
    {
        return $this->ref->getFileName();
    }

    public function getContentWithAdditionalMethods(string $additionalMethods, array &$namespaces = []): string
    {
        $currentLine = 0;
        $classContent = '';
        $classEndLine = $this->getEndLine();
        $classStartLine = $this->getStartLine();

        foreach ($this->filesystem->lines($this->getFileName()) as $line) {
            $currentLine++;

            if ($currentLine < $classStartLine) {
                preg_match(static::NAMESPACE_LINE_PATTERN, $line, $matches);
                $namespace = $matches[1] ?? null;
                if ($namespace && !in_array($namespace, $namespaces)) {
                    $namespaces[$namespace] = $namespace;
                }

                continue;
            }

            if ($currentLine === $classEndLine) {
                $additionalMethods = $this->isEmpty() ? ltrim($additionalMethods, "\n") : $additionalMethods;
                $classContent .= $additionalMethods . $line;

                break;
            }

            $classContent .= "$line\n";
        }

        return $classContent;
    }
}
