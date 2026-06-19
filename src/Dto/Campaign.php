<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;

/**
 * A campaign — the central unit defining what the system can send.
 *
 * @property-read int                        $id
 * @property-read int                        $tenant_id
 * @property-read string                     $key
 * @property-read string                     $name
 * @property-read string|null                $description
 * @property-read array<int, string>         $channels
 * @property-read string                     $trigger_type
 * @property-read string                     $category
 * @property-read bool                       $is_active
 * @property-read array<string, mixed>|null  $settings
 * @property-read TemplateSummary[]|null      $templates
 * @property-read DateTimeImmutable          $created_at
 * @property-read DateTimeImmutable          $updated_at
 */
class Campaign extends DataTransferObject
{
    protected int $id;
    protected int $tenant_id;
    protected string $key;
    protected string $name;
    protected ?string $description;
    protected array $channels;
    protected string $trigger_type;
    protected string $category;
    protected bool $is_active;
    protected ?array $settings;
    protected ?array $templates;
    protected DateTimeImmutable $created_at;
    protected DateTimeImmutable $updated_at;

    /**
     * {@inheritdoc}
     */
    protected static function casts(): array
    {
        return [
            'templates'  => [TemplateSummary::class],
            'created_at' => DateTimeImmutable::class,
            'updated_at' => DateTimeImmutable::class,
        ];
    }
}
