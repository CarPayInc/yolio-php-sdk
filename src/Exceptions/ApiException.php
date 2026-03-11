<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for all Messaging Hub API errors.
 *
 * Wraps HTTP error responses and connection failures into a single
 * exception type that callers can catch and inspect.
 */
class ApiException extends RuntimeException
{
    /** @var int|null The HTTP status code returned by the API, or null for connection errors */
    private ?int $statusCode;

    /**
     * Create a new API exception.
     *
     * @param string         $message    Human-readable error description
     * @param int|null       $statusCode The HTTP status code, or null for non-HTTP errors
     * @param Throwable|null $previous   The underlying exception, if any
     */
    public function __construct(string $message, ?int $statusCode = null, ?Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the HTTP status code that caused this exception.
     *
     * Returns null when the error is not tied to an HTTP response
     * (e.g. connection timeout, DNS failure).
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
