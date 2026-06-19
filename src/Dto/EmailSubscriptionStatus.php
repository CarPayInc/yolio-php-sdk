<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * The opt-out status for a single email address.
 *
 * @property-read string             $email
 * @property-read array<int, string> $opted_out_categories
 * @property-read bool               $is_fully_opted_out
 */
class EmailSubscriptionStatus extends DataTransferObject
{
    protected string $email;
    protected array $opted_out_categories;
    protected bool $is_fully_opted_out;
}
