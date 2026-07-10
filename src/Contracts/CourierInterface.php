<?php

namespace Rajibbinalam\BagistoCourier\Contracts;

use Rajibbinalam\BagistoCourier\DTO\CourierResponse;
use Rajibbinalam\BagistoCourier\DTO\OrderData;

/**
 * Every courier driver (SteadFast, Pathao, RedX, ...) must implement this
 * contract. Adding a brand-new courier only requires a new class that
 * implements this interface and is registered in config/courier.php -
 * no core package code needs to change.
 */
interface CourierInterface
{
    /**
     * Unique machine name of the courier, e.g. "steadfast", "pathao".
     */
    public function getCode(): string;

    /**
     * Create a consignment/order on the courier's platform.
     */
    public function createOrder(OrderData $order): CourierResponse;

    /**
     * Cancel a previously created consignment.
     */
    public function cancelOrder(string $consignmentId): CourierResponse;

    /**
     * Fetch the latest tracking/status information for a consignment.
     */
    public function trackOrder(string $consignmentId): CourierResponse;

    /**
     * Fetch / generate a printable label for the consignment (if supported).
     */
    public function printLabel(string $consignmentId): CourierResponse;

    /**
     * Estimate the delivery charge for a shipment before creating it.
     */
    public function calculateCharge(array $data): CourierResponse;

    /**
     * Return a normalized status string (pending, picked, in_transit,
     * delivered, returned, cancelled) for the given consignment.
     */
    public function getStatus(string $consignmentId): string;
}
