<?php

namespace Rajibbinalam\BagistoCourier\DTO;

/**
 * Uniform response returned by every driver method so the rest of the
 * package never has to know the shape of a specific courier's API payload.
 */
final class CourierResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $consignmentId = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $status = null,
        public readonly ?string $labelUrl = null,
        public readonly ?float $charge = null,
        public readonly array $raw = [],
        public readonly ?string $message = null,
    ) {
    }

    public static function success(array $attributes = [], array $raw = []): self
    {
        return new self(
            success: true,
            consignmentId: $attributes['consignment_id'] ?? null,
            trackingNumber: $attributes['tracking_number'] ?? null,
            status: $attributes['status'] ?? null,
            labelUrl: $attributes['label_url'] ?? null,
            charge: $attributes['charge'] ?? null,
            raw: $raw,
            message: $attributes['message'] ?? 'Request completed successfully.',
        );
    }

    public static function failed(string $message, array $raw = []): self
    {
        return new self(
            success: false,
            raw: $raw,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success'          => $this->success,
            'consignment_id'   => $this->consignmentId,
            'tracking_number'  => $this->trackingNumber,
            'status'           => $this->status,
            'label_url'        => $this->labelUrl,
            'charge'           => $this->charge,
            'message'          => $this->message,
        ];
    }
}
