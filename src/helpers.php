<?php

if (!function_exists('std_object_has')) {
    function std_object_has(stdClass $object, string $propertyName): bool
    {
        return isset(get_object_vars($object)[$propertyName]);
    }
}
