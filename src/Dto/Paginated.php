<?php

declare(strict_types=1);

namespace Yolio\Sdk\Dto;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A paginated collection of DTOs.
 *
 * Wraps the Laravel pagination envelope. The `data` property holds the hydrated
 * items for the current page; the remaining properties carry the pagination
 * metadata (snake_case, matching the wire format). Implements IteratorAggregate
 * and Countable so the current page's items can be iterated and counted directly.
 *
 * This is a bespoke collection rather than a reflection-backed DTO because its
 * item type varies per call (resolved by the supplied item factory).
 *
 * @implements IteratorAggregate<int, DataTransferObject>
 */
class Paginated implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var array<int, DataTransferObject> The hydrated items on the current page */
    public array $data = [];

    /** @var int */
    public int $current_page = 1;

    /** @var int */
    public int $per_page = 0;

    /** @var int */
    public int $total = 0;

    /** @var int */
    public int $last_page = 1;

    /** @var int|null */
    public ?int $from = null;

    /** @var int|null */
    public ?int $to = null;

    /** @var string|null */
    public ?string $first_page_url = null;

    /** @var string|null */
    public ?string $last_page_url = null;

    /** @var string|null */
    public ?string $next_page_url = null;

    /** @var string|null */
    public ?string $prev_page_url = null;

    /** @var string|null */
    public ?string $path = null;

    /** @var array<int, PaginationLink> */
    public array $links = [];

    /**
     * Hydrate a paginated response, mapping each item through a factory.
     *
     * @param array<string, mixed> $response    The decoded paginated response
     * @param callable             $itemFactory A callable mapping an item array to a DTO (e.g. [Campaign::class, 'make'])
     *
     * @return self
     */
    public static function fromArray(array $response, callable $itemFactory): self
    {
        $paginated = new self();

        $paginated->data = array_map(
            static fn (array $item): DataTransferObject => $itemFactory($item),
            $response['data'] ?? [],
        );

        $paginated->current_page   = (int) ($response['current_page'] ?? 1);
        $paginated->per_page       = (int) ($response['per_page'] ?? 0);
        $paginated->total          = (int) ($response['total'] ?? 0);
        $paginated->last_page      = (int) ($response['last_page'] ?? 1);
        $paginated->from           = isset($response['from']) ? (int) $response['from'] : null;
        $paginated->to             = isset($response['to']) ? (int) $response['to'] : null;
        $paginated->first_page_url = $response['first_page_url'] ?? null;
        $paginated->last_page_url  = $response['last_page_url'] ?? null;
        $paginated->next_page_url  = $response['next_page_url'] ?? null;
        $paginated->prev_page_url  = $response['prev_page_url'] ?? null;
        $paginated->path           = $response['path'] ?? null;

        $paginated->links = array_map(
            static fn (array $link): PaginationLink => PaginationLink::make($link),
            $response['links'] ?? [],
        );

        return $paginated;
    }

    /**
     * @return ArrayIterator<int, DataTransferObject>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'current_page'   => $this->current_page,
            'data'           => array_map(static fn (DataTransferObject $item): array => $item->toArray(), $this->data),
            'first_page_url' => $this->first_page_url,
            'from'           => $this->from,
            'last_page'      => $this->last_page,
            'last_page_url'  => $this->last_page_url,
            'links'          => array_map(static fn (PaginationLink $link): array => $link->toArray(), $this->links),
            'next_page_url'  => $this->next_page_url,
            'path'           => $this->path,
            'per_page'       => $this->per_page,
            'prev_page_url'  => $this->prev_page_url,
            'to'             => $this->to,
            'total'          => $this->total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
