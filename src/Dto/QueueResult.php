<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * The result of a scheduled (queue) request.
 *
 * @property-read string $message
 * @property-read int    $id
 * @property-read string $message_id
 */
class QueueResult extends DataTransferObject
{
    protected string $message;
    protected int $id;
    protected string $message_id;
}
