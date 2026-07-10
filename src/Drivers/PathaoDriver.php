<?php

namespace Rajibbinalam\BagistoCourier\Drivers;

use Illuminate\Support\Facades\Cache;
use Rajibbinalam\BagistoCourier\DTO\CourierResponse;
use Rajibbinalam\BagistoCourier\DTO\OrderData;

/**
 * Driver for Pathao Courier (https://pathao.com).
 * Reference API: https://github.com/na0260/pathao-api-laravel-package
 *
 * Pathao uses OAuth2 password-grant style authentication: client_id +
 * client_secret + username + password are exchanged for a short-lived
 * access token, which is then cached and reused until it expires.
 *
 * NOTE: verify the exact endpoint paths/field names (store_id, city_id,
 * zone_id, area_id, item_type, delivery_type, etc.) against Pathao's
 * current merchant API docs before going live.
 */
class PathaoDriver extends AbstractCourierDriver
{
    public function getCode(): string
    {
        return 'pathao';
    }

    protected function baseUrl(): string
    {
        return $this->config['sandbox'] ?? false
            ? ($this->config['sandbox_url'] ?? 'https://courier-api-sandbox.pathao.com')
            : ($this->config['base_url'] ?? 'https://api-hermes.pathao.com');
    }

    protected function requiredCredentialKeys(): array
    {
        return ['client_id', 'client_secret', 'username', 'password'];
    }

    /**
     * Fetch (and cache) an OAuth access token. Cached per-store so we don't
     * hit the token endpoint on every single API call.
     */
    protected function accessToken(): string
    {
        $cacheKey = 'bagisto_courier.pathao.token.' . md5($this->config['client_id']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $body = $this->request('POST', 'aladdin/api/v1/issue-token', [
                'json' => [
                    'client_id'     => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'username'      => $this->config['username'],
                    'password'      => $this->config['password'],
                    'grant_type'    => 'password',
                ],
            ]);

            return $body['access_token'] ?? '';
        });
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    public function createOrder(OrderData $order): CourierResponse
    {
        $payload = [
            'store_id'              => $this->config['store_id'] ?? null,
            'merchant_order_id'     => $order->invoiceOrOrderNumber,
            'recipient_name'        => $order->recipientName,
            'recipient_phone'       => $order->recipientPhone,
            'recipient_address'     => $order->recipientAddress,
            'recipient_city'        => $order->recipientCity,
            'recipient_zone'        => $order->recipientZone,
            'recipient_area'        => $order->recipientArea,
            'delivery_type'         => 48, // 48 = Normal Delivery per Pathao docs
            'item_type'             => 2,  // 2 = Parcel
            'special_instruction'   => $order->itemDescription,
            'item_quantity'         => $order->itemQuantity,
            'item_weight'           => $order->itemWeight,
            'amount_to_collect'     => $order->codAmount,
        ];

        $body = $this->request('POST', 'aladdin/api/v1/orders', [
            'headers' => $this->authHeaders(),
            'json'    => array_filter($payload, fn ($v) => $v !== null),
        ]);

        $data = $body['data'] ?? null;

        if (($body['type'] ?? null) !== 'success' || ! $data) {
            return CourierResponse::failed($body['message'] ?? 'Failed to create Pathao order.', $body);
        }

        return CourierResponse::success([
            'consignment_id'  => (string) ($data['consignment_id'] ?? ''),
            'tracking_number' => $data['consignment_id'] ?? null,
            'status'          => $data['order_status'] ?? 'pending',
            'message'         => 'Pathao order created successfully.',
        ], $body);
    }

    public function cancelOrder(string $consignmentId): CourierResponse
    {
        return CourierResponse::failed(
            'Pathao does not expose a public order-cancellation endpoint. Please cancel from the Pathao merchant panel.'
        );
    }

    public function trackOrder(string $consignmentId): CourierResponse
    {
        $body = $this->request('GET', "aladdin/api/v1/orders/{$consignmentId}/info", [
            'headers' => $this->authHeaders(),
        ]);

        $data = $body['data'] ?? null;

        if (($body['type'] ?? null) !== 'success' || ! $data) {
            return CourierResponse::failed($body['message'] ?? 'Unable to fetch tracking status.', $body);
        }

        return CourierResponse::success([
            'consignment_id' => $consignmentId,
            'status'         => $data['order_status'] ?? 'unknown',
            'message'        => 'Tracking status fetched.',
        ], $body);
    }

    public function printLabel(string $consignmentId): CourierResponse
    {
        return CourierResponse::failed('Label printing is managed from the Pathao merchant panel.');
    }

    public function calculateCharge(array $data): CourierResponse
    {
        $body = $this->request('POST', 'aladdin/api/v1/merchant/price-plan', [
            'headers' => $this->authHeaders(),
            'json'    => $data,
        ]);

        if (($body['type'] ?? null) !== 'success') {
            return CourierResponse::failed($body['message'] ?? 'Unable to calculate charge.', $body);
        }

        return CourierResponse::success([
            'charge'  => $body['data']['price'] ?? null,
            'message' => 'Charge calculated.',
        ], $body);
    }

    public function getStatus(string $consignmentId): string
    {
        $response = $this->trackOrder($consignmentId);

        return $this->normalizeStatus($response->status ?? 'pending');
    }

    protected function normalizeStatus(string $raw): string
    {
        return match (strtolower($raw)) {
            'pending', 'pickup_pending' => 'pending',
            'picked', 'pickup_success'  => 'picked',
            'in_transit', 'on_the_way', 'delivery_pending' => 'in_transit',
            'delivered'                 => 'delivered',
            'returned', 'return'        => 'returned',
            'cancelled', 'canceled'     => 'cancelled',
            default                     => 'pending',
        };
    }
}
