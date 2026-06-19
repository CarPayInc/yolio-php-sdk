<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * Request payload for creating or updating a campaign.
 *
 * All fields are optional at the SDK level; the API enforces which are required
 * for a given operation. Only the fields you set are sent.
 *
 * @property string|null                    $key
 * @property string|null                    $name
 * @property string|null                    $description
 * @property array<int, string>|null        $channels
 * @property string|null                    $trigger_type
 * @property string|null                    $category
 * @property bool|null                       $is_active
 * @property array<string, mixed>|null      $settings
 */
class CampaignRequest extends RequestObject
{
    protected ?string $key;
    protected ?string $name;
    protected ?string $description;
    protected ?array $channels;
    protected ?string $trigger_type;
    protected ?string $category;
    protected ?bool $is_active;
    protected ?array $settings;
}
