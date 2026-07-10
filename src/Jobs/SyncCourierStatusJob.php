<?php

namespace Rajibbinalam\BagistoCourier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rajibbinalam\BagistoCourier\Actions\SyncCourierStatusAction;
use Rajibbinalam\BagistoCourier\Models\CourierOrder;

class SyncCourierStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected CourierOrder $courierOrder)
    {
        $this->onQueue(config('courier.queue', 'courier'));
    }

    public function handle(SyncCourierStatusAction $action): void
    {
        $action->execute($this->courierOrder);
    }
}
