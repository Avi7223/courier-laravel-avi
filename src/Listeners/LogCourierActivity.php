<?php

namespace Rajibbinalam\BagistoCourier\Listeners;

use Illuminate\Support\Facades\Log;
use Rajibbinalam\BagistoCourier\Events\CourierOrderCreated;
use Rajibbinalam\BagistoCourier\Events\CourierStatusUpdated;

class LogCourierActivity
{
    public function handleOrderCreated(CourierOrderCreated $event): void
    {
        Log::channel('courier')->info('Courier order created', [
            'order_id'       => $event->courierOrder->order_id,
            'courier'        => $event->courierOrder->courier,
            'consignment_id' => $event->courierOrder->consignment_id,
        ]);
    }

    public function handleStatusUpdated(CourierStatusUpdated $event): void
    {
        Log::channel('courier')->info('Courier status updated', [
            'order_id' => $event->courierOrder->order_id,
            'from'     => $event->previousStatus,
            'to'       => $event->newStatus,
        ]);
    }
}
