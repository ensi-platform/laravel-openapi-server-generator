<?php

namespace Ensi\LaravelOpenApiServerGenerator\Utils;

class PhpDocGenerator
{
    public function fromText(string $text, int $spaces = 0, bool $deleteEmptyLines = false): string
    {
        $eol = PHP_EOL;
        $result = $this->prependSpaces("/**{$eol}", $spaces);
        foreach ($this->convertTextToLines($text, $deleteEmptyLines) as $line) {
            $result .= $this->prependSpaces(" * {$this->safeLine($line)}{$eol}", $spaces);
        }
        $result .= $this->prependSpaces(" */", $spaces);

        return $result;
    }

    private function prependSpaces(string $result, int $spaces = 0): string
    {
        return str_repeat(' ', $spaces) . $result;
    }

    private function safeLine(string $line): string
    {
        return str_replace('*/', '', $line);
    }

    private function convertTextToLines(string $text, bool $deleteEmptyLines): array
    {
        $lines = explode("\n", $text);
        $trimmedLines = array_map(fn ($line) => trim($line), $lines);

        return $deleteEmptyLines ? array_filter($trimmedLines) : $trimmedLines;
    }
}
