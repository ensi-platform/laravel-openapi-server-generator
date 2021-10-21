<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

use Illuminate\Filesystem\Filesystem;

class TemplatesManager
{
    public function __construct(private Filesystem $filesystem, private string $fallbackPath)
    {
    }

    public function setFallbackPath(string $fallbackPath): self
    {
        $this->fallbackPath = $fallbackPath;

        return $this;
    }

    public function getTemplate(string $templateName): string
    {
        return $this->filesystem->get($this->getTemplatePath($templateName));
    }

    public function getTemplatePath(string $templateName): string
    {
        $customPath = rtrim($this->fallbackPath, '/') . "/" . $templateName;

        return $this->fallbackPath && $this->filesystem->exists($customPath) ? $customPath : __DIR__ . '/../../templates/' . $templateName;
    }
}
