<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;

/**
 * A template — the per-campaign, per-channel, per-locale message definition.
 *
 * @property-read int                       $id
 * @property-read int                       $campaign_id
 * @property-read string                    $key
 * @property-read string                    $channel
 * @property-read string                    $locale
 * @property-read string|null               $subject
 * @property-read string|null               $body
 * @property-read array<string, mixed>|null $settings
 * @property-read bool                      $is_active
 * @property-read DateTimeImmutable         $created_at
 * @property-read DateTimeImmutable         $updated_at
 */
class Template extends DataTransferObject
{
    protected int $id;
    protected int $campaign_id;
    protected string $key;
    protected string $channel;
    protected string $locale;
    protected ?string $subject;
    protected ?string $body;
    protected ?array $settings;
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
