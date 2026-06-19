<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * The outcome for a single item within a batch request.
 *
 * @property-read int                                  $index
 * @property-read string                               $status
 * @property-read string|null                          $message_id
 * @property-read int|null                             $id
 * @property-read string|null                          $error
 * @property-read array<string, array<int, string>>|null $errors
 */
class BatchItemResult extends DataTransferObject
{
    protected int $index;
    protected string $status;
    protected ?string $message_id;
    protected ?int $id;
    protected ?string $error;
    protected ?array $errors;

    /**
     * Determine whether the item was accepted.
     *
     * @return bool
     */
    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }
}
