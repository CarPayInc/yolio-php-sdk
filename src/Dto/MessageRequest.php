<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * Request payload for sending or queuing a message (and for batch items).
 *
 * All fields are optional at the SDK level; the API enforces which are required.
 * Only the fields you set are sent. Include `scheduled_at` for queue operations.
 *
 * @property string|null                            $campaign_key
 * @property string|null                            $template_key
 * @property string|null                            $recipient_address
 * @property string|null                            $recipient_name
 * @property string|null                            $recipient_ref
 * @property array<string, mixed>|null              $data
 * @property array<string, mixed>|null              $metadata
 * @property string|null                            $from
 * @property string|null                            $from_email
 * @property string|null                            $from_name
 * @property string|null                            $reply_to_email
 * @property string|null                            $reply_to_name
 * @property array<int, array<string, mixed>>|null  $attachments
 * @property string|null                            $scheduled_at
 */
class MessageRequest extends RequestObject
{
    protected ?string $campaign_key;
    protected ?string $template_key;
    protected ?string $recipient_address;
    protected ?string $recipient_name;
    protected ?string $recipient_ref;
    protected ?array $data;
    protected ?array $metadata;
    protected ?string $from;
    protected ?string $from_email;
    protected ?string $from_name;
    protected ?string $reply_to_email;
    protected ?string $reply_to_name;
    protected ?array $attachments;
    protected ?string $scheduled_at;
}
