<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeInterface;
use JsonSerializable;
use ReflectionProperty;
use RuntimeException;

/**
 * Base class for SDK request (input) objects.
 *
 * Subclasses declare their fields as typed `protected` properties (snake_case,
 * matching the wire format). Unlike the response DTO base, a request object is
 * partial: nothing is required, no defaults are pre-filled, and `toArray()`
 * emits **only the fields that were explicitly set**. This makes partial
 * updates behave like a hand-built array — and lets you send an explicit `null`
 * to clear a field, which is distinct from simply leaving it unset.
 *
 * Build via the `make()` factory or by assigning properties:
 *
 *     $req = CampaignRequest::make(['key' => 'welcome', 'name' => 'Welcome']);
 *     // or
 *     $req = CampaignRequest::make();
 *     $req->key  = 'welcome';
 *     $req->name = 'Welcome';
 */
abstract class RequestObject implements JsonSerializable
{
    use ValidatesPropertyTypes;

    /** @var array<string, mixed> Explicitly-set field values */
    private array $__values = [];

    /** @var array<string, ReflectionProperty> Declared properties keyed by name */
    private array $propertyMap = [];

    /**
     * @param array<string, mixed> $parameters Initial field values (snake_case keys)
     *
     * @throws DataTransferObjectException
     */
    protected function __construct(array $parameters = [])
    {
        foreach (static::getAssignableProperties() as $property) {
            $this->propertyMap[$property->getName()] = $property;

            if (\PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }

            unset($this->{$property->getName()});
        }

        $this->set($parameters);
    }

    /**
     * Build a request object from an array of (snake_case) parameters.
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
     * Set one field (name, value) or many (associative array).
     *
     * @param string|array<string, mixed> $name
     * @param mixed                       $value
     *
     * @return static
     *
     * @throws DataTransferObjectException
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
     * Get an explicitly-set field value, or null if it was not set.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws DataTransferObjectException
     */
    public function get(string $name)
    {
        $this->assertPropertyExists($name);

        return $this->__values[$name] ?? null;
    }

    /**
     * Convert the request to its (snake_case) wire-format array.
     *
     * Only explicitly-set fields are included. Nested request objects become
     * arrays and DateTimeImmutable values become ISO 8601 strings.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [];

        foreach ($this->__values as $name => $value) {
            $out[$name] = $this->normalizeForArray($value);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeForArray($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.uP');
        }

        if ($value instanceof self || $value instanceof DataTransferObject) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeForArray($item), $value);
        }

        return $value;
    }

    /**
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
                    "Request property '%s' in class %s must be protected, not public.",
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
     * @param string $name
     *
     * @return void
     *
     * @throws DataTransferObjectException
     */
    private function assertPropertyExists(string $name): void
    {
        if ( ! isset($this->propertyMap[$name])) {
            throw DataTransferObjectException::nonexistentProperty(static::class, $name);
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
        $this->validatePropertyType($this->propertyMap[$name], $value);
        $this->__values[$name] = $value;
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

        return $this->__values[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name): bool
    {
        return isset($this->__values[$name]);
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
}
