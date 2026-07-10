<?php

namespace Rajibbinalam\BagistoCourier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Rajibbinalam\BagistoCourier\Actions\CreateCourierOrderAction;
use Rajibbinalam\BagistoCourier\DTO\OrderData;
use Rajibbinalam\BagistoCourier\Exceptions\CourierException;

class CreateCourierOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds, doubled by Laravel's exponential backoff when combined with retryUntil

    public function __construct(
        protected array $orderData,
        protected ?string $courierCode = null,
    ) {
        $this->onQueue(config('courier.queue', 'courier'));
    }

    public function handle(CreateCourierOrderAction $action): void
    {
        try {
            $action->execute(OrderData::fromArray($this->orderData), $this->courierCode);
        } catch (CourierException $e) {
            Log::channel('courier')->error('CreateCourierOrderJob failed', [
                'order_id' => $this->orderData['order_id'] ?? null,
                'error'    => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
