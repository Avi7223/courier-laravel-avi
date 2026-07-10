<?php

namespace Rajibbinalam\BagistoCourier\Repositories;

use Rajibbinalam\BagistoCourier\DTO\CourierResponse;
use Rajibbinalam\BagistoCourier\Models\CourierOrder;

class CourierOrderRepository
{
    public function findByOrderId(int|string $orderId): ?CourierOrder
    {
        return CourierOrder::where('order_id', $orderId)->first();
    }

    public function storeFromResponse(int|string $orderId, string $courier, CourierResponse $response): CourierOrder
    {
        return CourierOrder::updateOrCreate(
            ['order_id' => $orderId],
            [
                'courier'         => $courier,
                'consignment_id'  => $response->consignmentId,
                'tracking_number' => $response->trackingNumber,
                'status'          => $response->status ?? 'pending',
                'label_url'       => $response->labelUrl,
                'charge'          => $response->charge,
                'meta'            => $response->raw,
                'last_synced_at'  => now(),
            ]
        );
    }

    public function updateStatus(CourierOrder $courierOrder, string $status, array $raw = []): CourierOrder
    {
        $courierOrder->update([
            'status'         => $status,
            'meta'           => array_merge($courierOrder->meta ?? [], $raw),
            'last_synced_at' => now(),
        ]);

        return $courierOrder;
    }

    /**
     * Orders that are not yet in a terminal state (delivered/returned/
     * cancelled) and are therefore worth polling again.
     */
    public function pendingSync()
    {
        return CourierOrder::whereNotIn('status', ['delivered', 'returned', 'cancelled'])
            ->whereNotNull('consignment_id')
            ->get();
    }
}
