<?php

namespace Rajibbinalam\BagistoCourier\Events;

use Rajibbinalam\BagistoCourier\Models\CourierOrder;

class CourierStatusUpdated
{
    public function __construct(
        public readonly CourierOrder $courierOrder,
        public readonly string $previousStatus,
        public readonly string $newStatus,
    ) {
    }
}
