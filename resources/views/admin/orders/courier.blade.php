{{--
    Include this partial inside Bagisto's admin order-view Blade template
    (packages/Webkul/Admin/src/Resources/views/orders/view.blade.php or
    wherever your Bagisto version renders the order summary), e.g.:

        @include('bagisto-courier::admin.orders.courier', ['order' => $order])

    See README > "Order Integration" for exact placement instructions,
    since the surrounding markup differs slightly between Bagisto versions.
--}}
@php
    $courierOrder = app(\Rajibbinalam\BagistoCourier\Repositories\CourierOrderRepository::class)
        ->findByOrderId($order->id);
@endphp

<div class="bg-white rounded p-4 mt-4 border">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">Courier</h3>

        @if (! $courierOrder)
            <form action="{{ route('admin.courier.create', $order->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-lg btn-primary">
                    Create Courier Order
                </button>
            </form>
        @else
            <form action="{{ route('admin.courier.sync', $order->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    Refresh Status
                </button>
            </form>
        @endif
    </div>

    @if ($courierOrder)
        <table class="w-full text-sm">
            <tbody>
                <tr>
                    <td class="py-1 text-gray-500">Courier</td>
                    <td class="py-1 font-medium">{{ ucfirst($courierOrder->courier) }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-500">Tracking ID</td>
                    <td class="py-1 font-medium">{{ $courierOrder->tracking_number ?? $courierOrder->consignment_id }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-500">Status</td>
                    <td class="py-1 font-medium capitalize">{{ str_replace('_', ' ', $courierOrder->status) }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-500">Last Synced</td>
                    <td class="py-1 font-medium">{{ $courierOrder->last_synced_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="text-sm text-gray-500">No courier order created yet for this order.</p>
    @endif
</div>
