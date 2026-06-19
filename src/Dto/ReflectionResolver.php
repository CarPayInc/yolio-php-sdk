<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use ReflectionClass;

/**
 * Caches ReflectionClass instances so repeated DTO hydration does not re-reflect.
 */
final class ReflectionResolver
{
    /** @var array<string, ReflectionClass> */
    private static array $resolved = [];

    /**
     * Resolve (and cache) a ReflectionClass for the given class name.
     *
     * @param string $className The fully-qualified class name
     *
     * @return ReflectionClass
     */
    public static function resolve(string $className): ReflectionClass
    {
        if ( ! isset(self::$resolved[$className])) {
            self::$resolved[$className] = new ReflectionClass($className);
        }

        return self::$resolved[$className];
    }
}
