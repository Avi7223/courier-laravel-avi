<?php

namespace Rajibbinalam\BagistoCourier\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Links a Bagisto order to a consignment on whichever courier handled it.
 * Kept intentionally separate from Bagisto's own Order model so this
 * package never needs to modify core Bagisto tables/migrations.
 */
class CourierOrder extends Model
{
    protected $table = 'courier_orders';

    protected $fillable = [
        'order_id',
        'courier',
        'consignment_id',
        'tracking_number',
        'status',
        'label_url',
        'charge',
        'meta',
        'last_synced_at',
    ];

    protected $casts = [
        'meta'            => 'array',
        'charge'          => 'float',
        'last_synced_at'  => 'datetime',
    ];

    public function order()
    {
        // Bagisto's order model class, resolved loosely so we don't hard
        // depend on Bagisto's namespace at static-analysis time.
        return $this->belongsTo(\Webkul\Sales\Models\Order::class, 'order_id');
    }
}
