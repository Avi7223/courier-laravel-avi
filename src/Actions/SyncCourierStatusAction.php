<?php

namespace Rajibbinalam\BagistoCourier\Actions;

use Rajibbinalam\BagistoCourier\Events\CourierStatusUpdated;
use Rajibbinalam\BagistoCourier\Models\CourierOrder;
use Rajibbinalam\BagistoCourier\Repositories\CourierOrderRepository;
use Rajibbinalam\BagistoCourier\Services\CourierManager;

class SyncCourierStatusAction
{
    public function __construct(
        protected CourierManager $manager,
        protected CourierOrderRepository $repository,
    ) {
    }

    public function execute(CourierOrder $courierOrder): CourierOrder
    {
        $driver   = $this->manager->driver($courierOrder->courier);
        $response = $driver->trackOrder($courierOrder->consignment_id);

        if (! $response->success) {
            // Leave the last known status untouched; the failure is
            // already logged by the driver's request() wrapper.
            return $courierOrder;
        }

        $newStatus = $driver->getStatus($courierOrder->consignment_id);
        $previous  = $courierOrder->status;

        $courierOrder = $this->repository->updateStatus($courierOrder, $newStatus, $response->raw);

        if ($previous !== $newStatus) {
            event(new CourierStatusUpdated($courierOrder, $previous, $newStatus));
        }

        return $courierOrder;
    }

    /**
     * Sync every order that isn't in a terminal state yet. Used by the
     * scheduled command / cron entry point.
     */
    public function executeAll(): int
    {
        $orders = $this->repository->pendingSync();

        foreach ($orders as $order) {
            $this->execute($order);
        }

        return $orders->count();
    }
}
