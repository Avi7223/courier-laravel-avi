<?php

namespace Rajibbinalam\BagistoCourier\Exceptions;

/**
 * Thrown when a courier's API key/secret/token is missing or rejected.
 * Caught centrally so the admin always sees a friendly message instead
 * of a raw stack trace.
 */
class InvalidCredentialsException extends CourierException
{
    public static function forCourier(string $courier): self
    {
        return new self("Credentials for the courier \"{$courier}\" are missing or invalid. Please check Configure > Sales > Courier Settings.");
    }
}
