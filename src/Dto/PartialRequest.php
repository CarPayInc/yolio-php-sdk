<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * Request payload for creating or updating a template partial.
 *
 * All fields are optional at the SDK level; the API enforces which are required
 * for a given operation. Only the fields you set are sent.
 *
 * @property string|null $key
 * @property string|null $description
 * @property string|null $body
 * @property bool|null    $is_active
 */
class PartialRequest extends RequestObject
{
    protected ?string $key;
    protected ?string $description;
    protected ?string $body;
    protected ?bool $is_active;
}
