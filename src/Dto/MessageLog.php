<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use DateTimeImmutable;

/**
 * A message log entry — the record of a sent (or attempted) message.
 *
 * The lookup endpoint may also return this shape for a still-scheduled message
 * resolved from the queue, in which case `id` and `channel` are null, delivery
 * fields are null, and `scheduled_at` is populated.
 *
 * @property-read int|null                            $id
 * @property-read string                              $message_id
 * @property-read int                                 $tenant_id
 * @property-read int|null                            $campaign_id
 * @property-read int|null                            $template_id
 * @property-read string|null                         $channel
 * @property-read string                              $recipient_address
 * @property-read string|null                         $recipient_name
 * @property-read string|null                         $recipient_ref
 * @property-read string|null                         $from_email
 * @property-read string|null                         $from_name
 * @property-read string|null                         $reply_to_email
 * @property-read string|null                         $reply_to_name
 * @property-read string|null                         $from_number
 * @property-read string|null                         $subject
 * @property-read string|null                         $body
 * @property-read array<int, array<string, mixed>>|null $attachments_meta
 * @property-read string                              $status
 * @property-read string|null                         $provider
 * @property-read string|null                         $provider_message_id
 * @property-read string|null                         $error_message
 * @property-read array<string, mixed>|null           $metadata
 * @property-read DateTimeImmutable|null              $scheduled_at
 * @property-read DateTimeImmutable|null              $sent_at
 * @property-read DateTimeImmutable|null              $delivered_at
 * @property-read DateTimeImmutable                   $created_at
 * @property-read DateTimeImmutable                   $updated_at
 */
class MessageLog extends DataTransferObject
{
    protected ?int $id;
    protected string $message_id;
    protected int $tenant_id;
    protected ?int $campaign_id;
    protected ?int $template_id;
    protected ?string $channel;
    protected string $recipient_address;
    protected ?string $recipient_name;
    protected ?string $recipient_ref;
    protected ?string $from_email;
    protected ?string $from_name;
    protected ?string $reply_to_email;
    protected ?string $reply_to_name;
    protected ?string $from_number;
    protected ?string $subject;
    protected ?string $body;
    protected ?array $attachments_meta;
    protected string $status;
    protected ?string $provider;
    protected ?string $provider_message_id;
    protected ?string $error_message;
    protected ?array $metadata;
    protected ?DateTimeImmutable $scheduled_at;
    protected ?DateTimeImmutable $sent_at;
    protected ?DateTimeImmutable $delivered_at;
    protected DateTimeImmutable $created_at;
    protected DateTimeImmutable $updated_at;

    /**
     * {@inheritdoc}
     */
    protected static function casts(): array
    {
        return [
            'scheduled_at' => DateTimeImmutable::class,
            'sent_at'      => DateTimeImmutable::class,
            'delivered_at' => DateTimeImmutable::class,
            'created_at'   => DateTimeImmutable::class,
            'updated_at'   => DateTimeImmutable::class,
        ];
    }
}
