<?php

namespace Rajibbinalam\BagistoCourier\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Rajibbinalam\BagistoCourier\Contracts\CourierInterface;
use Rajibbinalam\BagistoCourier\Exceptions\CourierApiException;
use Rajibbinalam\BagistoCourier\Exceptions\InvalidCredentialsException;

abstract class AbstractCourierDriver implements CourierInterface
{
    protected Client $client;

    /**
     * @param array $config Resolved credentials/settings for this courier,
     *                       e.g. ['api_key' => ..., 'secret_key' => ..., 'base_url' => ..., 'sandbox' => bool]
     */
    public function __construct(protected array $config)
    {
        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl(), '/') . '/',
            'timeout'  => (float) ($config['timeout'] ?? 15),
        ]);
    }

    abstract protected function baseUrl(): string;

    /**
     * Fields required in $config for this driver to function. Used both
     * for a runtime guard and to drive admin form validation.
     */
    abstract protected function requiredCredentialKeys(): array;

    protected function assertCredentials(): void
    {
        foreach ($this->requiredCredentialKeys() as $key) {
            if (empty($this->config[$key])) {
                throw InvalidCredentialsException::forCourier($this->getCode());
            }
        }
    }

    /**
     * Centralized request wrapper: logs every request/response pair to
     * storage/logs/courier.log and converts transport errors into a
     * CourierApiException instead of leaking Guzzle exceptions upward.
     */
    protected function request(string $method, string $uri, array $options = []): array
    {
        $this->assertCredentials();

        $logContext = [
            'courier' => $this->getCode(),
            'method'  => $method,
            'uri'     => $uri,
        ];

        try {
            Log::channel('courier')->info('Courier API request', $logContext + ['payload' => $this->redact($options)]);

            $response = $this->client->request($method, $uri, $options);
            $body     = (string) $response->getBody();
            $decoded  = json_decode($body, true) ?? [];

            Log::channel('courier')->info('Courier API response', $logContext + ['status' => $response->getStatusCode(), 'body' => $decoded]);

            return $decoded;
        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 0;
            $body   = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();

            Log::channel('courier')->error('Courier API error', $logContext + ['status' => $status, 'body' => $body]);

            throw CourierApiException::fromResponse($this->getCode(), $status, $body);
        } catch (GuzzleException $e) {
            Log::channel('courier')->error('Courier API network error', $logContext + ['error' => $e->getMessage()]);

            throw CourierApiException::networkError($this->getCode(), $e->getMessage());
        }
    }

    /**
     * Strip secrets out of anything we write to the log file.
     */
    protected function redact(array $payload): array
    {
        foreach (['api_key', 'secret_key', 'client_secret', 'password', 'token', 'authorization'] as $sensitive) {
            if (isset($payload['headers'][$sensitive])) {
                $payload['headers'][$sensitive] = '***redacted***';
            }
            if (isset($payload['json'][$sensitive])) {
                $payload['json'][$sensitive] = '***redacted***';
            }
        }

        return $payload;
    }

    public function calculateCharge(array $data): \Rajibbinalam\BagistoCourier\DTO\CourierResponse
    {
        // Not every courier exposes a charge-estimation endpoint publicly.
        // Drivers that support it should override this method.
        return \Rajibbinalam\BagistoCourier\DTO\CourierResponse::failed(
            "{$this->getCode()} does not support charge calculation."
        );
    }
}
