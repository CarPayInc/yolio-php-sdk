<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * Request payload for creating or updating a template.
 *
 * All fields are optional at the SDK level; the API enforces which are required
 * for a given operation. Only the fields you set are sent.
 *
 * @property string|null               $key
 * @property string|null               $channel
 * @property string|null               $locale
 * @property string|null               $subject
 * @property string|null               $body
 * @property array<string, mixed>|null $settings
 * @property bool|null                  $is_active
 */
class TemplateRequest extends RequestObject
{
    protected ?string $key;
    protected ?string $channel;
    protected ?string $locale;
    protected ?string $subject;
    protected ?string $body;
    protected ?array $settings;
    protected ?bool $is_active;
}
