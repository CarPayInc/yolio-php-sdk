<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;

/**
 * A reusable template partial.
 *
 * @property-read int               $id
 * @property-read int               $tenant_id
 * @property-read string            $key
 * @property-read string|null       $description
 * @property-read string            $body
 * @property-read bool              $is_active
 * @property-read DateTimeImmutable $created_at
 * @property-read DateTimeImmutable $updated_at
 */
class TemplatePartial extends DataTransferObject
{
    protected int $id;
    protected int $tenant_id;
    protected string $key;
    protected ?string $description;
    protected string $body;
    protected bool $is_active;
    protected DateTimeImmutable $created_at;
    protected DateTimeImmutable $updated_at;

    /**
     * {@inheritdoc}
     */
    protected static function casts(): array
    {
        return [
            'created_at' => DateTimeImmutable::class,
            'updated_at' => DateTimeImmutable::class,
        ];
    }
}
