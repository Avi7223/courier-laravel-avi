<?php

namespace Rajibbinalam\BagistoCourier\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Rajibbinalam\BagistoCourier\Contracts\CourierInterface;
use Rajibbinalam\BagistoCourier\Exceptions\CourierException;

/**
 * Central place that turns a courier "code" (steadfast, pathao, redx, ...)
 * into a ready-to-use driver instance with its credentials injected.
 *
 * Adding a new courier = add one line to config/courier.php's "drivers"
 * map. Nothing else in the package needs to change.
 */
class CourierManager
{
    /** @var array<string, CourierInterface> */
    protected array $resolved = [];

    /**
     * Resolve the driver currently selected by the admin
     * (Configure > Sales > Courier Settings > Default Courier),
     * or a specific $code if provided.
     */
    public function driver(?string $code = null): CourierInterface
    {
        $code ??= $this->defaultCourierCode();

        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $drivers = Config::get('courier.drivers', []);

        if (! isset($drivers[$code])) {
            throw new CourierException("No courier driver is registered for \"{$code}\". Check config/courier.php.");
        }

        /** @var class-string<CourierInterface> $driverClass */
        $driverClass = $drivers[$code];

        return $this->resolved[$code] = App::make($driverClass, [
            'config' => $this->credentialsFor($code),
        ]);
    }

    /**
     * Reads the admin-selected default courier. Falls back to Laravel's
     * config file so the package also works outside a full Bagisto
     * install (e.g. plain Laravel / tests).
     */
    public function defaultCourierCode(): string
    {
        if (function_exists('core')) {
            $configured = core()->getConfigData('sales.courier.general.default_courier');

            if (! empty($configured)) {
                return $configured;
            }
        }

        return Config::get('courier.default', 'steadfast');
    }

    /**
     * Pulls per-courier credentials either from Bagisto's core_config_data
     * (via the `core()` helper) when running inside Bagisto, or from
     * config/courier.php as a fallback.
     */
    public function credentialsFor(string $code): array
    {
        $fallback = Config::get("courier.credentials.{$code}", []);

        if (! function_exists('core')) {
            return $fallback;
        }

        $prefix = "sales.courier.{$code}";
        $fields  = Config::get("courier.admin_fields.{$code}", []);
        $result  = [];

        foreach (array_keys($fields) as $field) {
            $value = core()->getConfigData("{$prefix}.{$field}");
            $result[$field] = $value !== null && $value !== '' ? $value : ($fallback[$field] ?? null);
        }

        return $result;
    }

    public function availableCouriers(): array
    {
        return array_keys(Config::get('courier.drivers', []));
    }
}
