<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;

/**
 * An email unsubscribe record for a recipient.
 *
 * @property-read int                $id
 * @property-read string             $email
 * @property-read array<int, string> $categories
 * @property-read DateTimeImmutable  $created_at
 * @property-read DateTimeImmutable  $updated_at
 */
class EmailSubscription extends DataTransferObject
{
    protected int $id;
    protected string $email;
    protected array $categories;
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
