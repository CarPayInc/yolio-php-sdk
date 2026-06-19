<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use Exception;
use Throwable;

/**
 * Raised when a DTO is constructed with invalid, missing, or mistyped data.
 */
class DataTransferObjectException extends Exception
{
    /**
     * @param string         $message  The error message
     * @param int            $code     The error code
     * @param Throwable|null $previous The previous throwable, if any
     */
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $class        The DTO class
     * @param string $propertyName The offending property name
     *
     * @return self
     */
    public static function nonexistentProperty(string $class, string $propertyName): self
    {
        return new static(sprintf('Property $%s does not exist in class %s.', $propertyName, $class));
    }

    /**
     * @param string $class        The DTO class
     * @param string $propertyName The offending property name
     *
     * @return self
     */
    public static function propertyNotInitialized(string $class, string $propertyName): self
    {
        return new static(sprintf('%s::$%s must not be accessed before initialization.', $class, $propertyName));
    }

    /**
     * @param string $class        The DTO class
     * @param string $errorMessage The JSON error detail
     *
     * @return self
     */
    public static function invalidJsonData(string $class, string $errorMessage): self
    {
        return new static(sprintf('JSON initialization input in class %s is invalid: %s', $class, $errorMessage));
    }

    /**
     * @param string $class        The DTO class
     * @param string $propertyName The missing property name
     *
     * @return self
     */
    public static function requiredPropertyMissing(string $class, string $propertyName): self
    {
        return new static(sprintf('Required property $%s is not present during construction of class %s.', $propertyName, $class));
    }

    /**
     * @param string $class        The DTO class
     * @param string $propertyName The offending property name
     * @param string $expectedType The declared type
     * @param mixed  $actualValue  The value that was supplied
     *
     * @return self
     */
    public static function invalidPropertyType(string $class, string $propertyName, string $expectedType, $actualValue): self
    {
        $actualType = is_object($actualValue) ? get_class($actualValue) : gettype($actualValue);

        return new static(sprintf(
            'Invalid type for property $%s in class %s. Expected %s, got %s.',
            $propertyName,
            $class,
            $expectedType,
            $actualType
        ));
    }
}
