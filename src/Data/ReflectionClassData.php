<?php

namespace Ensi\LaravelOpenApiServerGenerator\Data;

use Illuminate\Support\Collection;
use ReflectionClass;

class ReflectionClassData
{
    protected ReflectionClass $class;
    protected ?Collection $methods = null;

    public function __construct(
        public readonly string $className,
        public readonly string $namespace,
    ) {
        $this->class = new ReflectionClass("$namespace\\$this->className");
    }

    public function isEmpty(): bool
    {
        return $this->getMethods()->isEmpty();
    }

    public function getMethods(): Collection
    {
        if (!$this->methods) {
            $this->methods = collect($this->class->getMethods())->keyBy('name');
        }

        return $this->methods;
    }

    public function hasMethod(string $methodName): bool
    {
        return $this->getMethods()->has($methodName);
    }

    public function getStartLine(bool $withoutComments = false): int
    {
        $comments = $this->class->getDocComment();
        if ($withoutComments || !$comments) {
            return $this->class->getStartLine();
        }

        return $this->class->getStartLine() - count(explode("\n", $comments));
    }

    public function getEndLine(): int
    {
        return $this->class->getEndLine();
    }
}
