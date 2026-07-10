<?php

namespace Rajibbinalam\BagistoCourier\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Rajibbinalam\BagistoCourier\Console\Commands\SyncCourierStatusCommand;
use Rajibbinalam\BagistoCourier\Events\CourierOrderCreated;
use Rajibbinalam\BagistoCourier\Events\CourierStatusUpdated;
use Rajibbinalam\BagistoCourier\Listeners\LogCourierActivity;
use Rajibbinalam\BagistoCourier\Services\CourierManager;

class CourierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/courier.php', 'courier');

        $this->app->singleton(CourierManager::class, fn ($app) => new CourierManager());

        $this->app->alias(CourierManager::class, 'courier');
    }

    public function boot(): void
    {
        $this->configureLogging();
        $this->publishAssets();
        $this->loadMigrations();
        $this->loadRoutes();
        $this->mergeBagistoAdminConfig();
        $this->registerEvents();
        $this->registerCommands();
    }

    /**
     * Adds a dedicated "courier" log channel writing to
     * storage/logs/courier.log, without requiring the host app to edit
     * config/logging.php manually.
     */
    protected function configureLogging(): void
    {
        $this->app['config']->set('logging.channels.courier', [
            'driver' => 'daily',
            'path'   => storage_path('logs/courier.log'),
            'level'  => env('COURIER_LOG_LEVEL', 'info'),
            'days'   => 14,
        ]);
    }

    protected function publishAssets(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/courier.php' => config_path('courier.php'),
        ], 'courier-config');

        $this->publishes([
            __DIR__ . '/../../config/system.php' => config_path('bagisto-courier-system.php'),
        ], 'courier-system-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'courier-migrations');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/bagisto-courier'),
        ], 'courier-views');
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /**
     * Registers this package's admin routes inside Bagisto's existing
     * "admin" route group so they automatically inherit Bagisto's admin
     * auth + ACL middleware, session, and locale handling.
     */
    protected function loadRoutes(): void
    {
        Route::middleware(['web', 'admin_locale'])
            ->group(__DIR__ . '/../../routes/admin-routes.php');
    }

    /**
     * Merges this package's Configure-page schema into Bagisto's core
     * system config, so "Courier Settings" shows up under
     * Configure > Sales without editing any core Bagisto file.
     */
    protected function mergeBagistoAdminConfig(): void
    {
        $existing = config('core::system') ?? config('system') ?? [];
        $ours     = require __DIR__ . '/../../config/system.php';

        $this->app['config']->set('core::system', array_merge((array) $existing, $ours));

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'bagisto-courier');
    }

    protected function registerEvents(): void
    {
        Event::listen(CourierOrderCreated::class, [LogCourierActivity::class, 'handleOrderCreated']);
        Event::listen(CourierStatusUpdated::class, [LogCourierActivity::class, 'handleStatusUpdated']);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncCourierStatusCommand::class,
            ]);
        }
    }
}
