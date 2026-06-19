<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * A single pagination link from a paginated response.
 *
 * @property-read string|null $url
 * @property-read string      $label
 * @property-read bool        $active
 */
class PaginationLink extends DataTransferObject
{
    protected ?string $url;
    protected string $label;
    protected bool $active;
}
