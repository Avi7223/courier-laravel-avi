# Bagisto Courier

Enterprise-grade, multi-courier integration package for [Bagisto](https://bagisto.com), built as a standard Composer/Laravel package. Ships with **SteadFast** and **Pathao** drivers out of the box, and is designed so a new courier (RedX, Sundarban, Paperfly, eCourier, Delivery Tiger, DHL, FedEx, UPS, ...) can be added by writing **one driver class** — no core package code needs to change.

---

## Features

- PSR-4 autoloaded Composer package, installable with `composer require`
- Laravel package auto-discovery (service provider registers itself)
- Driver pattern behind a single `CourierInterface` contract
- Bagisto admin integration: **Configure → Sales → Courier Settings**
- Per-courier dynamic credential forms, stored via Bagisto's secure config system (never hardcoded)
- `courier_orders` table linking Bagisto orders ↔ courier consignments
- Queue-backed order creation and status sync (no blocking API calls on the storefront/admin request cycle)
- Dedicated `storage/logs/courier.log` channel for every request/response
- Friendly, centralized exception handling (`InvalidCredentialsException`, `CourierApiException`)
- Artisan command + cron-ready entry point for automatic status sync
- Events (`CourierOrderCreated`, `CourierStatusUpdated`) so you can hook in your own notifications, webhooks, etc.

---

## Requirements

- PHP ^8.1
- Laravel 9/10/11 (Bagisto v1.x / v2.x)
- Guzzle ^7.5

---

## Installation

### 1. Require via Composer

Once published to GitHub, add it to your Bagisto project's `composer.json` (until it's on Packagist, reference it as a VCS repository):

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/rajibbinalam/bagisto-courier"
        }
    ]
}
```

```bash
composer require rajibbinalam/bagisto-courier:dev-main
```

(Once you tag releases and/or submit it to Packagist, this simplifies to just `composer require rajibbinalam/bagisto-courier`.)

### 2. Publish config, migrations, and views

```bash
php artisan vendor:publish --tag=courier-config
php artisan vendor:publish --tag=courier-migrations
php artisan vendor:publish --tag=courier-views
```

### 3. Run the migration

```bash
php artisan migrate
```

This creates the `courier_orders` table.

### 4. Clear config cache

```bash
php artisan optimize:clear
```

---

## Configuration (Admin Panel)

Go to:

```
Admin → Configure → Sales → Courier Settings
```

- **Default Courier** — select SteadFast or Pathao (or any custom driver you've added).
- **SteadFast** — API Key, Secret Key, Base URL, Sandbox Mode, Enable/Disable.
- **Pathao** — Client ID, Client Secret, Username, Password, Store ID, Base URL, Sandbox Mode, Enable/Disable.

All values are stored through Bagisto's own `core_config_data` configuration system — nothing is hardcoded, and secrets are never written to version control.

> **Version note:** Bagisto's exact "Configure" tree (how sections map to sidebar tabs) has shifted slightly across releases. `config/system.php` in this package follows the Bagisto v2.x convention. If **Courier Settings** doesn't appear where you expect after installing, open one of Bagisto's own `packages/Webkul/*/src/Config/system.php` files, compare the array shape, and adjust the `key` values in this package's `config/system.php` to match. See **Troubleshooting** below.

---

## Order Integration

### Add the "Create Courier Order" button + tracking box

Include the provided partial inside Bagisto's admin order-view Blade template (the file differs slightly by version, typically under `packages/Webkul/Admin/src/Resources/views/orders/view.blade.php` or an override in your theme):

```blade
@include('bagisto-courier::admin.orders.courier', ['order' => $order])
```

This renders:

- A **Create Courier Order** button (only shown if no courier order exists yet for this order)
- A **Refresh Status** button once a courier order exists
- A tracking summary: Courier, Tracking ID, Status, Last Synced

The button POSTs to routes already registered inside Bagisto's `admin` route group (so Bagisto's admin auth/ACL middleware applies automatically):

```
POST admin/orders/{orderId}/courier/create
POST admin/orders/{orderId}/courier/sync
```

### What happens on click

1. `CreateCourierOrderJob` is dispatched to the `courier` queue.
2. The job resolves the admin-selected courier driver via `CourierManager`.
3. The driver calls the courier's API (`createOrder`).
4. On success, a `courier_orders` row is created/updated and a `CourierOrderCreated` event fires.
5. Refreshing the order page shows the Consignment ID, Tracking Number, and Status.

---

## Queue Setup

All courier API calls run through the queue so admin/storefront requests never block on a slow courier response, and failed calls can retry automatically.

```bash
php artisan queue:work --queue=courier
```

In production, run this under Supervisor (recommended) so it restarts automatically:

```ini
[program:bagisto-courier-worker]
command=php /path/to/bagisto/artisan queue:work --queue=courier --tries=3 --sleep=3
autostart=true
autorestart=true
numprocs=2
```

You can rename the queue via `COURIER_QUEUE` in `.env`.

---

## Auto Status Sync (Cron)

Status flows through: `pending → picked → in_transit → delivered / returned / cancelled`.

### Option A — Laravel Scheduler (recommended)

Add to your Bagisto app's `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('courier:sync-status')->everyFiveMinutes();
}
```

Make sure the Laravel scheduler cron entry exists on the server:

```
* * * * * cd /path/to/bagisto && php artisan schedule:run >> /dev/null 2>&1
```

### Option B — Direct server cron (no scheduler)

```
*/5 * * * * cd /path/to/bagisto && php artisan courier:sync-status >> /dev/null 2>&1
```

---

## Logging

Every courier API request and response (with secrets redacted) is logged to:

```
storage/logs/courier-YYYY-MM-DD.log
```

via a dedicated `courier` log channel (daily rotation, 14-day retention by default — adjust in `config/courier.php` / `.env` with `COURIER_LOG_LEVEL`).

---

## Events

| Event | Fired when |
|---|---|
| `Rajibbinalam\BagistoCourier\Events\CourierOrderCreated` | A consignment is successfully created |
| `Rajibbinalam\BagistoCourier\Events\CourierStatusUpdated` | A tracked order's status changes |

Listen to these from your own Bagisto app's `EventServiceProvider` to trigger SMS/email notifications, Slack alerts, etc.

---

## Creating a Custom Driver

No core package code needs to change. To add, say, **RedX**:

```php
namespace App\Couriers;

use Rajibbinalam\BagistoCourier\Drivers\AbstractCourierDriver;
use Rajibbinalam\BagistoCourier\DTO\{CourierResponse, OrderData};

class RedxDriver extends AbstractCourierDriver
{
    public function getCode(): string { return 'redx'; }

    protected function baseUrl(): string { return $this->config['base_url']; }

    protected function requiredCredentialKeys(): array
    {
        return ['api_token'];
    }

    public function createOrder(OrderData $order): CourierResponse { /* ... */ }
    public function cancelOrder(string $consignmentId): CourierResponse { /* ... */ }
    public function trackOrder(string $consignmentId): CourierResponse { /* ... */ }
    public function printLabel(string $consignmentId): CourierResponse { /* ... */ }
    public function getStatus(string $consignmentId): string { /* ... */ }
}
```

Register it:

```php
// config/courier.php
'drivers' => [
    'steadfast' => \Rajibbinalam\BagistoCourier\Drivers\SteadFastDriver::class,
    'pathao'    => \Rajibbinalam\BagistoCourier\Drivers\PathaoDriver::class,
    'redx'      => \App\Couriers\RedxDriver::class,
],
```

Then add its fields to `config/system.php` (admin form) and `admin_fields` in `config/courier.php` (so `CourierManager` knows which core-config keys to read).

---

## API Usage (programmatic)

```php
use Rajibbinalam\BagistoCourier\Actions\CreateCourierOrderAction;
use Rajibbinalam\BagistoCourier\DTO\OrderData;

$action = app(CreateCourierOrderAction::class);

$courierOrder = $action->execute(OrderData::fromArray([
    'order_id'                => 1042,
    'invoice_or_order_number' => '1000001042',
    'recipient_name'          => 'John Doe',
    'recipient_phone'         => '01700000000',
    'recipient_address'       => '123 Main Rd, Dhaka',
    'cod_amount'              => 1500,
]));
```

Or resolve a specific driver directly:

```php
$driver = app(\Rajibbinalam\BagistoCourier\Services\CourierManager::class)->driver('pathao');
$response = $driver->trackOrder('CONSIGNMENT123');
```

---

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| "Courier Settings" tab missing from Configure → Sales | Bagisto version's `system.php` tree shape differs — compare against `packages/Webkul/Sales/src/Config/system.php` (or similar) and adjust `config/system.php` keys in this package. |
| `InvalidCredentialsException` even after saving keys | Confirm the admin config field paths (`sales.courier.<courier>.<field>`) match what `CourierManager::credentialsFor()` reads — check with `core()->getConfigData('sales.courier.steadfast.api_key')` in `php artisan tinker`. |
| Jobs never run | Make sure a queue worker is running: `php artisan queue:work --queue=courier`, and `QUEUE_CONNECTION` isn't `sync` if you want true async behavior. |
| Status never updates | Confirm the scheduler/cron entry is active: `php artisan courier:sync-status` should run without error manually first. |

---

## Security

- No secrets are ever hardcoded in this package.
- All credentials are read from Bagisto's `core_config_data` (or `.env` as a fallback outside Bagisto).
- Logs redact `api_key`, `secret_key`, `client_secret`, `password`, and `token` fields.
- Exceptions never leak raw courier API response bodies to the admin UI — only to `storage/logs/courier.log`.

---

## License

MIT
