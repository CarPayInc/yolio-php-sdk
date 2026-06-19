<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

/**
 * The aggregate result of a batch send or queue request.
 *
 * @property-read int                     $accepted
 * @property-read int                     $rejected
 * @property-read BatchItemResult[]       $results
 */
class BatchResult extends DataTransferObject
{
    protected int $accepted;
    protected int $rejected;
    protected array $results;

    /**
     * {@inheritdoc}
     */
    protected static function casts(): array
    {
        return [
            'results' => [BatchItemResult::class],
        ];
    }
}
