<?php

namespace Rajibbinalam\BagistoCourier\Events;

use Rajibbinalam\BagistoCourier\Models\CourierOrder;

class CourierOrderCreated
{
    public function __construct(public readonly CourierOrder $courierOrder)
    {
    }
}
