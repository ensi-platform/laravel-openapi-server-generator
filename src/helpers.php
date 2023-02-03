<?php

use Symfony\Component\Console\Output\ConsoleOutput;

if (!function_exists('std_object_has')) {
    function std_object_has(stdClass $object, string $propertyName): bool
    {
        return isset(get_object_vars($object)[$propertyName]);
    }
}

if (!function_exists('do_with_all_of')) {
    function do_with_all_of(stdClass $object, callable $fn): void
    {
        $fn($object);
        if (std_object_has($object, 'allOf')) {
            foreach ($object->allOf as $allOfItem) {
                do_with_all_of($allOfItem, $fn);
            }
        }
    }
}

if (!function_exists('console_warning')) {
    function console_warning(string $text, Throwable $e = null): void
    {
        $output = resolve(ConsoleOutput::class);

        if ($e) {
            $text .= "\r\n{$e->getCode()}: {$e->getMessage()}";
        }

        $output->writeln("<comment>$text</comment>");
    }
}
