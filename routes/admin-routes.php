<?php

use Illuminate\Support\Facades\Route;
use Rajibbinalam\BagistoCourier\Http\Controllers\Admin\CourierOrderController;

/**
 * These routes are registered inside the Bagisto admin route group
 * (prefix "admin", middleware "admin") by CourierServiceProvider, so they
 * automatically inherit Bagisto's admin authentication + ACL middleware.
 */
Route::prefix('admin/orders')->name('admin.courier.')->group(function () {
    Route::post('{orderId}/courier/create', [CourierOrderController::class, 'store'])->name('create');
    Route::post('{orderId}/courier/sync', [CourierOrderController::class, 'sync'])->name('sync');
});
