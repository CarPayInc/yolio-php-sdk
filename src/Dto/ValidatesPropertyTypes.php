<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use ReflectionNamedType;
use ReflectionProperty;

/**
 * Shared property-type validation for DTOs and request objects.
 *
 * Validates a value against a property's declared scalar or class type. Used to
 * fail fast when a field is hydrated (or set) with an incompatible value.
 */
trait ValidatesPropertyTypes
{
    /**
     * Validate a value against a property's declared type.
     *
     * @param ReflectionProperty $property The property whose type to enforce
     * @param mixed              $value    The value to validate
     *
     * @return void
     *
     * @throws DataTransferObjectException When the value does not match the type
     */
    private function validatePropertyType(ReflectionProperty $property, $value): void
    {
        $type = $property->getType();

        if ($type === null || ($value === null && $type->allowsNull())) {
            return;
        }

        if ($type instanceof ReflectionNamedType && ! $this->isValueOfType($value, $type)) {
            throw DataTransferObjectException::invalidPropertyType(
                static::class,
                $property->getName(),
                $type->getName(),
                $value
            );
        }
    }

    /**
     * Determine whether a value satisfies a named type.
     *
     * @param mixed               $value The value to check
     * @param ReflectionNamedType $type  The declared type
     *
     * @return bool
     */
    private function isValueOfType($value, ReflectionNamedType $type): bool
    {
        switch ($type->getName()) {
            case 'int':
                return is_int($value);
            case 'float':
                return is_float($value) || is_int($value);
            case 'string':
                return is_string($value);
            case 'bool':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            case 'iterable':
                return is_iterable($value);
            default:
                $typeName = $type->getName();

                if (class_exists($typeName) || interface_exists($typeName)) {
                    return is_object($value) && $value instanceof $typeName;
                }

                return true;
        }
    }
}
