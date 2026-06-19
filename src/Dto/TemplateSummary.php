<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * A summary of a template as embedded in a campaign response.
 *
 * @property-read int         $id
 * @property-read string      $channel
 * @property-read string      $locale
 * @property-read string|null $template_type
 */
class TemplateSummary extends DataTransferObject
{
    protected int $id;
    protected string $channel;
    protected string $locale;
    protected ?string $template_type;
}
