<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * The result of an immediate send request.
 *
 * @property-read string $message
 * @property-read string $message_id
 */
class SendResult extends DataTransferObject
{
    protected string $message;
    protected string $message_id;
}
