<?php

/**
 * Bagisto admin "Configure" schema. Merged in by CourierServiceProvider so
 * a new "Courier Settings" tab appears under the "Sales" group, alongside
 * Bagisto's own "Shipping Settings" tab.
 *
 * IMPORTANT: Bagisto's exact system-config tree (group/section key names,
 * how "sections" map to sidebar tabs) can differ slightly between Bagisto
 * versions. This file follows the structure used by Bagisto v2.x
 * (packages/Webkul/*\/src/Config/system.php). If "Courier Settings" does not
 * appear where expected after installing, compare this array's shape
 * against your installed Bagisto's own config/system.php files and adjust
 * the "key" values to match (see README > Troubleshooting).
 */
return [
    [
        'key'  => 'sales',
        'name' => 'admin::app.configuration.index.sales',
        'sections' => [
            [
                'key'   => 'sales.courier',
                'name'  => 'Courier Settings',
                'info'  => 'Configure the couriers used for order fulfillment (SteadFast, Pathao, and any custom drivers you add).',
                'icon-class' => 'icon-shipping',
                'sort'  => 12,
                'fields' => [
                    [
                        'name'       => 'general.default_courier',
                        'title'     => 'Default Courier',
                        'type'      => 'select',
                        'validation'=> 'required',
                        'default'   => 'steadfast',
                        'options'   => [
                            ['title' => 'SteadFast', 'value' => 'steadfast'],
                            ['title' => 'Pathao', 'value' => 'pathao'],
                        ],
                    ],

                    // ---- SteadFast ----
                    [
                        'name'  => 'steadfast.active',
                        'title' => 'Enable SteadFast',
                        'type'  => 'boolean',
                        'default' => 0,
                    ],
                    [
                        'name'       => 'steadfast.api_key',
                        'title'     => 'API Key',
                        'type'      => 'password',
                        'validation'=> 'required_if:sales.courier.general.default_courier,steadfast',
                    ],
                    [
                        'name'       => 'steadfast.secret_key',
                        'title'     => 'Secret Key',
                        'type'      => 'password',
                        'validation'=> 'required_if:sales.courier.general.default_courier,steadfast',
                    ],
                    [
                        'name'    => 'steadfast.base_url',
                        'title'  => 'Base URL',
                        'type'   => 'text',
                        'default'=> 'https://portal.packzy.com/api/v1',
                        'validation' => 'url',
                    ],
                    [
                        'name'    => 'steadfast.sandbox',
                        'title'  => 'Sandbox Mode',
                        'type'   => 'boolean',
                        'default'=> 0,
                    ],

                    // ---- Pathao ----
                    [
                        'name'  => 'pathao.active',
                        'title' => 'Enable Pathao',
                        'type'  => 'boolean',
                        'default' => 0,
                    ],
                    [
                        'name'       => 'pathao.client_id',
                        'title'     => 'Client ID',
                        'type'      => 'text',
                        'validation'=> 'required_if:sales.courier.general.default_courier,pathao',
                    ],
                    [
                        'name'       => 'pathao.client_secret',
                        'title'     => 'Client Secret',
                        'type'      => 'password',
                        'validation'=> 'required_if:sales.courier.general.default_courier,pathao',
                    ],
                    [
                        'name'       => 'pathao.username',
                        'title'     => 'Username',
                        'type'      => 'text',
                        'validation'=> 'required_if:sales.courier.general.default_courier,pathao',
                    ],
                    [
                        'name'       => 'pathao.password',
                        'title'     => 'Password',
                        'type'      => 'password',
                        'validation'=> 'required_if:sales.courier.general.default_courier,pathao',
                    ],
                    [
                        'name'  => 'pathao.store_id',
                        'title' => 'Store ID',
                        'type'  => 'text',
                    ],
                    [
                        'name'    => 'pathao.base_url',
                        'title'  => 'Base URL',
                        'type'   => 'text',
                        'default'=> 'https://api-hermes.pathao.com',
                        'validation' => 'url',
                    ],
                    [
                        'name'    => 'pathao.sandbox',
                        'title'  => 'Sandbox Mode',
                        'type'   => 'boolean',
                        'default'=> 0,
                    ],
                ],
            ],
        ],
    ],
];
