<?php

namespace Rajibbinalam\BagistoCourier\DTO;

/**
 * Normalized shipment payload built from a Bagisto Order model.
 * Keeping this as a plain DTO means drivers never touch Eloquent models
 * directly, which keeps them portable and easy to unit test.
 */
final class OrderData
{
    public function __construct(
        public readonly int|string $orderId,
        public readonly string $invoiceOrOrderNumber,
        public readonly string $recipientName,
        public readonly string $recipientPhone,
        public readonly string $recipientAddress,
        public readonly ?string $recipientCity = null,
        public readonly ?string $recipientZone = null,
        public readonly ?string $recipientArea = null,
        public readonly float $codAmount = 0.0,
        public readonly ?string $itemDescription = null,
        public readonly int $itemQuantity = 1,
        public readonly float $itemWeight = 0.5, // kg
        public readonly array $meta = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            invoiceOrOrderNumber: $data['invoice_or_order_number'] ?? (string) $data['order_id'],
            recipientName: $data['recipient_name'],
            recipientPhone: $data['recipient_phone'],
            recipientAddress: $data['recipient_address'],
            recipientCity: $data['recipient_city'] ?? null,
            recipientZone: $data['recipient_zone'] ?? null,
            recipientArea: $data['recipient_area'] ?? null,
            codAmount: (float) ($data['cod_amount'] ?? 0),
            itemDescription: $data['item_description'] ?? null,
            itemQuantity: (int) ($data['item_quantity'] ?? 1),
            itemWeight: (float) ($data['item_weight'] ?? 0.5),
            meta: $data['meta'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'order_id'                => $this->orderId,
            'invoice_or_order_number' => $this->invoiceOrOrderNumber,
            'recipient_name'          => $this->recipientName,
            'recipient_phone'         => $this->recipientPhone,
            'recipient_address'      => $this->recipientAddress,
            'recipient_city'          => $this->recipientCity,
            'recipient_zone'          => $this->recipientZone,
            'recipient_area'          => $this->recipientArea,
            'cod_amount'              => $this->codAmount,
            'item_description'        => $this->itemDescription,
            'item_quantity'            => $this->itemQuantity,
            'item_weight'              => $this->itemWeight,
            'meta'                     => $this->meta,
        ];
    }
}
