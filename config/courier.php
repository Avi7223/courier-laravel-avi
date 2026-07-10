<?php

use Rajibbinalam\BagistoCourier\Drivers\PathaoDriver;
use Rajibbinalam\BagistoCourier\Drivers\SteadFastDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Courier
    |--------------------------------------------------------------------------
    | Used only as a fallback when running outside a full Bagisto install
    | (e.g. tests). Inside Bagisto, the admin's selection under
    | Configure > Sales > Courier Settings always takes priority.
    */
    'default' => env('COURIER_DEFAULT', 'steadfast'),

    /*
    |--------------------------------------------------------------------------
    | Registered Drivers
    |--------------------------------------------------------------------------
    | Map of courier "code" => driver class. To add a brand-new courier:
    |   1. Create App\Couriers\RedxDriver implementing CourierInterface
    |      (or extend AbstractCourierDriver).
    |   2. Add 'redx' => \App\Couriers\RedxDriver::class below.
    |   3. Add its admin fields to config/system.php and admin_fields below.
    | No other package code needs to change.
    */
    'drivers' => [
        'steadfast' => SteadFastDriver::class,
        'pathao'    => PathaoDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Field Map
    |--------------------------------------------------------------------------
    | Declares which core_config_data fields belong to each courier, so
    | CourierManager knows what to read from Bagisto's admin configuration.
    | Keep this in sync with config/system.php.
    */
    'admin_fields' => [
        'steadfast' => [
            'api_key'    => null,
            'secret_key' => null,
            'base_url'   => null,
            'sandbox'    => null,
            'active'     => null,
        ],
        'pathao' => [
            'client_id'     => null,
            'client_secret' => null,
            'username'      => null,
            'password'      => null,
            'store_id'      => null,
            'base_url'      => null,
            'sandbox'       => null,
            'active'        => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Credentials (non-Bagisto / local testing only)
    |--------------------------------------------------------------------------
    */
    'credentials' => [
        'steadfast' => [
            'api_key'    => env('STEADFAST_API_KEY'),
            'secret_key' => env('STEADFAST_SECRET_KEY'),
            'base_url'   => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),
            'sandbox'    => env('STEADFAST_SANDBOX', false),
        ],
        'pathao' => [
            'client_id'     => env('PATHAO_CLIENT_ID'),
            'client_secret' => env('PATHAO_CLIENT_SECRET'),
            'username'      => env('PATHAO_USERNAME'),
            'password'      => env('PATHAO_PASSWORD'),
            'store_id'      => env('PATHAO_STORE_ID'),
            'base_url'      => env('PATHAO_BASE_URL', 'https://api-hermes.pathao.com'),
            'sandbox'       => env('PATHAO_SANDBOX', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('COURIER_QUEUE', 'courier'),
];
