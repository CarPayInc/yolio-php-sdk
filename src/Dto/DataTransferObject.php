<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use ReflectionProperty;
use RuntimeException;

/**
 * Base class for SDK data transfer objects.
 *
 * Subclasses declare their fields as typed `protected` properties; hydration
 * (`make()`) and serialization (`toArray()`) are derived from those properties
 * via reflection — no per-property mapping code is needed. Property names map
 * 1:1 to the API's snake_case wire keys.
 *
 * Subclasses may declare a `casts()` map to hydrate richer types: a field cast
 * to `DateTimeImmutable::class` is parsed from / formatted to an ISO 8601 string,
 * and a field cast to a DTO class (or `[DtoClass]` for a list) is hydrated into
 * nested DTO instance(s).
 */
abstract class DataTransferObject implements JsonSerializable
{
    use ValidatesPropertyTypes;

    /** @var array<string, mixed> The backing store for property values */
    private array $__values = [];

    /** @var array<int, string> Names of all declared (assignable) properties */
    private array $propertyNames = [];

    /** @var array<int, string> Property names excluded from toArray() */
    private array $excludedNames = [];

    /** @var array<int, string> Property names toArray() is restricted to */
    private array $onlyNames = [];

    /**
     * @param array<string, mixed> $parameters Initial field values (snake_case keys)
     *
     * @throws DataTransferObjectException
     */
    protected function __construct(array $parameters = [])
    {
        foreach (static::getAssignableProperties() as $property) {
            $this->propertyNames[] = $property->getName();

            // Required on PHP 7.4 to read protected properties via reflection;
            // a no-op (and deprecated) on PHP 8.1+, so skip it there.
            if (\PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }

            if ($property->isInitialized($this)) {
                $this->__values[$property->getName()] = $property->getValue($this);
            } elseif ($this->isNullableProperty($property)) {
                $this->__values[$property->getName()] = null;
            }

            unset($this->{$property->getName()});
        }

        $this->set($parameters);
        $this->applyCasts();
        $this->validateRequiredProperties();
    }

    /**
     * Declare type casts for fields. Override in subclasses.
     *
     * Each entry maps a field name to either `DateTimeImmutable::class`, a DTO
     * class name (single nested object), or `[DtoClass]` (a list of nested DTOs).
     *
     * @return array<string, mixed>
     */
    protected static function casts(): array
    {
        return [];
    }

    /**
     * Instantiate a DTO from an array of (snake_case) parameters.
     *
     * @param array<string, mixed> $parameters
     *
     * @return static
     *
     * @throws DataTransferObjectException
     */
    public static function make(array $parameters = []): self
    {
        return new static($parameters);
    }

    /**
     * Instantiate many DTOs from a list of parameter arrays.
     *
     * @param array<int, array<string, mixed>> $parameters
     *
     * @return array<int, static>
     *
     * @throws DataTransferObjectException
     */
    public static function makeFromMany(array $parameters = []): array
    {
        return array_map(static function (array $item): self {
            return static::make($item);
        }, $parameters);
    }

    /**
     * Set one field (name, value) or many (associative array).
     *
     * @param string|array<string, mixed> $name
     * @param mixed                       $value
     *
     * @return static
     */
    public function set($name, $value = null): self
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            $this->{$name} = $value;
        }

        return $this;
    }

    /**
     * Get a field value by name, or a default if unset.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     *
     * @throws DataTransferObjectException
     */
    public function get(string $name, $default = null)
    {
        $this->assertPropertyExists($name);

        return array_key_exists($name, $this->__values) ? $this->__values[$name] : $default;
    }

    /**
     * Convert the DTO to its (snake_case) wire-format array.
     *
     * Nested DTOs become arrays and DateTimeImmutable values become ISO 8601
     * strings, recursively.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = [];

        $names = array_keys($this->__values);

        if ($this->onlyNames) {
            $names = array_intersect($this->onlyNames, $names);
        }

        $names = array_diff($names, $this->excludedNames);

        foreach ($names as $name) {
            $arr[$name] = $this->normalizeForArray($this->__values[$name]);
        }

        return $arr;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Exclude specific fields from toArray() output.
     *
     * @param string ...$names
     *
     * @return static
     *
     * @throws DataTransferObjectException
     */
    public function except(string ...$names): self
    {
        $this->assertPropertyExists(...$names);
        $this->excludedNames = $names;

        return $this;
    }

    /**
     * Restrict toArray() output to the given fields.
     *
     * @param string ...$names
     *
     * @return static
     *
     * @throws DataTransferObjectException
     */
    public function only(string ...$names): self
    {
        $this->assertPropertyExists(...$names);
        $this->onlyNames = $names;

        return $this;
    }

    /**
     * Restrict toArray() output to non-null fields.
     *
     * @return static
     */
    public function compact(): self
    {
        $this->onlyNames = [];

        foreach ($this->__values as $name => $value) {
            if ($value !== null) {
                $this->onlyNames[] = $name;
            }
        }

        return $this;
    }

    /**
     * Apply the subclass's declared casts to the hydrated data.
     *
     * @return void
     */
    private function applyCasts(): void
    {
        foreach (static::casts() as $name => $cast) {
            if ( ! array_key_exists($name, $this->__values) || $this->__values[$name] === null) {
                continue;
            }

            $this->__values[$name] = $this->castValue($this->__values[$name], $cast);
        }
    }

    /**
     * Cast a single field value according to its cast spec.
     *
     * @param mixed        $value The raw value
     * @param string|array $cast  The cast spec (class name, or [class] for a list)
     *
     * @return mixed
     */
    private function castValue($value, $cast)
    {
        if (is_array($cast)) {
            if ( ! is_array($value)) {
                return $value;
            }

            $inner = $cast[0];

            return array_map(fn ($item) => $this->castScalar($item, $inner), $value);
        }

        return $this->castScalar($value, $cast);
    }

    /**
     * Cast a non-list value to a DateTimeImmutable or nested DTO.
     *
     * @param mixed  $value The raw value
     * @param string $cast  The target class name
     *
     * @return mixed
     */
    private function castScalar($value, string $cast)
    {
        if (is_object($value) && $value instanceof $cast) {
            return $value;
        }

        if ($cast === DateTimeImmutable::class) {
            return is_string($value) ? new DateTimeImmutable($value) : $value;
        }

        if (is_subclass_of($cast, self::class)) {
            return is_array($value) ? $cast::make($value) : $value;
        }

        return $value;
    }

    /**
     * Recursively normalize a value for array/JSON output.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeForArray($value)
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.uP');
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeForArray($item), $value);
        }

        return $value;
    }

    /**
     * Gather the concrete DTO's assignable (protected, non-static) properties.
     *
     * @return array<int, ReflectionProperty>
     */
    private static function getAssignableProperties(): array
    {
        $properties = ReflectionResolver::resolve(static::class)->getProperties(
            ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC
        );

        foreach ($properties as $property) {
            if ($property->isPublic()) {
                throw new RuntimeException(sprintf(
                    "DTO property '%s' in class %s must be protected, not public.",
                    $property->getName(),
                    static::class
                ));
            }
        }

        return array_filter(
            $properties,
            static fn (ReflectionProperty $property): bool => ! $property->isStatic()
        );
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return bool
     */
    private function isNullableProperty(ReflectionProperty $property): bool
    {
        $type = $property->getType();

        return $type === null || $type->allowsNull();
    }

    /**
     * @return void
     *
     * @throws DataTransferObjectException
     */
    private function validateRequiredProperties(): void
    {
        foreach (static::getAssignableProperties() as $property) {
            $name = $property->getName();

            if ( ! $this->isNullableProperty($property) && ! array_key_exists($name, $this->__values)) {
                throw DataTransferObjectException::requiredPropertyMissing(static::class, $name);
            }

            if (array_key_exists($name, $this->__values)) {
                $this->validatePropertyType($property, $this->__values[$name]);
            }
        }
    }

    /**
     * @param string ...$names
     *
     * @return void
     *
     * @throws DataTransferObjectException
     */
    private function assertPropertyExists(string ...$names): void
    {
        foreach ($names as $name) {
            if ( ! in_array($name, $this->propertyNames, true)) {
                throw DataTransferObjectException::nonexistentProperty(static::class, $name);
            }
        }
    }

    /**
     * @param string ...$names
     *
     * @return void
     *
     * @throws DataTransferObjectException
     */
    private function assertPropertyInitialized(string ...$names): void
    {
        foreach ($names as $name) {
            if ( ! array_key_exists($name, $this->__values)) {
                throw DataTransferObjectException::propertyNotInitialized(static::class, $name);
            }
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     *
     * @throws DataTransferObjectException
     */
    public function __set($name, $value): void
    {
        $this->assertPropertyExists($name);
        $this->__values[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function __unset($name): void
    {
        unset($this->__values[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws DataTransferObjectException
     */
    public function __get($name)
    {
        $this->assertPropertyExists($name);
        $this->assertPropertyInitialized($name);

        return $this->__values[$name];
    }
}
