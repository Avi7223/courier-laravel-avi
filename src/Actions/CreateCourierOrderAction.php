<?php

namespace Rajibbinalam\BagistoCourier\Actions;

use Rajibbinalam\BagistoCourier\DTO\OrderData;
use Rajibbinalam\BagistoCourier\Events\CourierOrderCreated;
use Rajibbinalam\BagistoCourier\Exceptions\CourierException;
use Rajibbinalam\BagistoCourier\Models\CourierOrder;
use Rajibbinalam\BagistoCourier\Repositories\CourierOrderRepository;
use Rajibbinalam\BagistoCourier\Services\CourierManager;

class CreateCourierOrderAction
{
    public function __construct(
        protected CourierManager $manager,
        protected CourierOrderRepository $repository,
    ) {
    }

    /**
     * @throws CourierException
     */
    public function execute(OrderData $order, ?string $courierCode = null): CourierOrder
    {
        $courierCode ??= $this->manager->defaultCourierCode();
        $driver = $this->manager->driver($courierCode);

        $response = $driver->createOrder($order);

        if (! $response->success) {
            throw new CourierException($response->message ?? 'Failed to create courier order.');
        }

        $courierOrder = $this->repository->storeFromResponse($order->orderId, $courierCode, $response);

        event(new CourierOrderCreated($courierOrder));

        return $courierOrder;
    }
}
