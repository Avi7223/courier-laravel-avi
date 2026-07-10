<?php

namespace Rajibbinalam\BagistoCourier\Exceptions;

/**
 * Thrown for network timeouts, 4xx/5xx courier API responses, or
 * malformed responses. The original response body is kept in $context
 * for logging, but is never shown directly to the admin.
 */
class CourierApiException extends CourierException
{
    public static function fromResponse(string $courier, int $statusCode, string $body): self
    {
        $exception = new self("The \"{$courier}\" courier API returned an error (HTTP {$statusCode}). See storage/logs/courier.log for details.");

        return $exception->setContext([
            'courier'     => $courier,
            'status_code' => $statusCode,
            'body'        => $body,
        ]);
    }

    public static function networkError(string $courier, string $reason): self
    {
        $exception = new self("Could not reach the \"{$courier}\" courier API: {$reason}");

        return $exception->setContext([
            'courier' => $courier,
            'reason'  => $reason,
        ]);
    }
}
