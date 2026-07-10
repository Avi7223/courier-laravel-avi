<?php

namespace Rajibbinalam\BagistoCourier\Drivers;

use Rajibbinalam\BagistoCourier\DTO\CourierResponse;
use Rajibbinalam\BagistoCourier\DTO\OrderData;

/**
 * Driver for SteadFast Courier (https://steadfast.com.bd).
 * Reference API: https://github.com/steadfast-it/SteadFast-Courier-Laravel-Package
 *
 * SteadFast authenticates every request with two headers:
 *   Api-Key: <api_key>
 *   Secret-Key: <secret_key>
 *
 * NOTE: verify the exact endpoint paths/field names against SteadFast's
 * current API documentation for your merchant account before going live -
 * couriers occasionally version or rename fields.
 */
class SteadFastDriver extends AbstractCourierDriver
{
    public function getCode(): string
    {
        return 'steadfast';
    }

    protected function baseUrl(): string
    {
        return $this->config['sandbox'] ?? false
            ? ($this->config['sandbox_url'] ?? 'https://sandbox.packzy.com/api/v1')
            : ($this->config['base_url'] ?? 'https://portal.packzy.com/api/v1');
    }

    protected function requiredCredentialKeys(): array
    {
        return ['api_key', 'secret_key'];
    }

    protected function headers(): array
    {
        return [
            'Api-Key'      => $this->config['api_key'],
            'Secret-Key'   => $this->config['secret_key'],
            'Content-Type' => 'application/json',
        ];
    }

    public function createOrder(OrderData $order): CourierResponse
    {
        $payload = [
            'invoice'        => $order->invoiceOrOrderNumber,
            'recipient_name' => $order->recipientName,
            'recipient_phone'=> $order->recipientPhone,
            'recipient_address' => $order->recipientAddress,
            'cod_amount'     => $order->codAmount,
            'note'           => $order->itemDescription,
        ];

        $body = $this->request('POST', 'create_order', [
            'headers' => $this->headers(),
            'json'    => $payload,
        ]);

        $consignment = $body['consignment'] ?? null;

        if (($body['status'] ?? null) !== 'success' || ! $consignment) {
            return CourierResponse::failed($body['message'] ?? 'Failed to create SteadFast order.', $body);
        }

        return CourierResponse::success([
            'consignment_id'  => (string) ($consignment['consignment_id'] ?? ''),
            'tracking_number' => $consignment['tracking_code'] ?? null,
            'status'          => $consignment['status'] ?? 'pending',
            'message'         => 'SteadFast consignment created successfully.',
        ], $body);
    }

    public function cancelOrder(string $consignmentId): CourierResponse
    {
        // SteadFast does not expose a public cancel endpoint at the time of
        // writing; cancellations are typically handled from their merchant
        // panel. We surface a clear message instead of pretending it worked.
        return CourierResponse::failed(
            'SteadFast does not currently support order cancellation via API. Please cancel from the SteadFast merchant panel.'
        );
    }

    public function trackOrder(string $consignmentId): CourierResponse
    {
        $body = $this->request('GET', "status_by_cid/{$consignmentId}", [
            'headers' => $this->headers(),
        ]);

        if (($body['status'] ?? null) !== 'success') {
            return CourierResponse::failed($body['message'] ?? 'Unable to fetch tracking status.', $body);
        }

        return CourierResponse::success([
            'consignment_id' => $consignmentId,
            'status'         => $body['delivery_status'] ?? 'unknown',
            'message'        => 'Tracking status fetched.',
        ], $body);
    }

    public function printLabel(string $consignmentId): CourierResponse
    {
        // SteadFast label generation is done from their merchant dashboard.
        return CourierResponse::failed('Label printing is managed from the SteadFast merchant panel.');
    }

    public function getStatus(string $consignmentId): string
    {
        $response = $this->trackOrder($consignmentId);

        return $this->normalizeStatus($response->status ?? 'pending');
    }

    protected function normalizeStatus(string $raw): string
    {
        return match (strtolower($raw)) {
            'pending'                => 'pending',
            'delivered'              => 'delivered',
            'partial_delivered'      => 'delivered',
            'cancelled', 'canceled'  => 'cancelled',
            'returned', 'return'     => 'returned',
            'in_review', 'in_transit', 'hold' => 'in_transit',
            default                  => 'pending',
        };
    }
}
