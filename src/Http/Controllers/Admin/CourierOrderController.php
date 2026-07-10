<?php

namespace Rajibbinalam\BagistoCourier\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Rajibbinalam\BagistoCourier\Actions\CreateCourierOrderAction;
use Rajibbinalam\BagistoCourier\Actions\SyncCourierStatusAction;
use Rajibbinalam\BagistoCourier\DTO\OrderData;
use Rajibbinalam\BagistoCourier\Exceptions\CourierException;
use Rajibbinalam\BagistoCourier\Jobs\CreateCourierOrderJob;
use Rajibbinalam\BagistoCourier\Repositories\CourierOrderRepository;

class CourierOrderController extends Controller
{
    public function __construct(
        protected CourierOrderRepository $repository,
        protected SyncCourierStatusAction $syncAction,
    ) {
    }

    /**
     * Handles the "Create Courier Order" button on the admin order view page.
     * Dispatches to the queue so the admin UI never blocks on a slow courier API.
     */
    public function store(int $orderId): RedirectResponse
    {
        $order = \Webkul\Sales\Models\Order::findOrFail($orderId);

        $orderData = $this->buildOrderDataFromBagistoOrder($order);

        CreateCourierOrderJob::dispatch($orderData->toArray());

        session()->flash('success', 'Courier order is being created in the background. Refresh in a few seconds to see the tracking details.');

        return redirect()->back();
    }

    /**
     * Manual "Refresh status" action from the order view page.
     */
    public function sync(int $orderId): RedirectResponse
    {
        $courierOrder = $this->repository->findByOrderId($orderId);

        if (! $courierOrder) {
            session()->flash('error', 'No courier order exists for this order yet.');

            return redirect()->back();
        }

        try {
            $this->syncAction->execute($courierOrder);
            session()->flash('success', 'Courier status refreshed.');
        } catch (CourierException $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->back();
    }

    protected function buildOrderDataFromBagistoOrder(\Webkul\Sales\Models\Order $order): OrderData
    {
        $address = $order->shipping_address;

        return OrderData::fromArray([
            'order_id'                 => $order->id,
            'invoice_or_order_number'  => $order->increment_id,
            'recipient_name'           => trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')),
            'recipient_phone'          => $address->phone ?? '',
            'recipient_address'       => trim(($address->address1[0] ?? '') . ', ' . ($address->city ?? '')),
            'recipient_city'           => $address->city ?? null,
            'cod_amount'               => $order->payment?->method === 'cashondelivery' ? (float) $order->grand_total : 0.0,
            'item_description'         => 'Order #' . $order->increment_id,
            'item_quantity'            => (int) $order->total_qty_ordered,
        ]);
    }
}
