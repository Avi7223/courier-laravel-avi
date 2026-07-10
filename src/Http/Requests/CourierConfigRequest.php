<?php

namespace Rajibbinalam\BagistoCourier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NOTE: Bagisto's own "Configure" pages validate and persist system.php
 * fields automatically through core_config_data — you generally will NOT
 * need to route through this request for the standard settings form.
 * This is provided for any *custom* admin screens you add on top (e.g. a
 * dedicated "Test Connection" button) where you want explicit validation.
 */
class CourierConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier' => ['required', 'in:steadfast,pathao'],

            'steadfast.api_key'    => ['required_if:courier,steadfast', 'string'],
            'steadfast.secret_key' => ['required_if:courier,steadfast', 'string'],
            'steadfast.base_url'   => ['nullable', 'url'],

            'pathao.client_id'     => ['required_if:courier,pathao', 'string'],
            'pathao.client_secret' => ['required_if:courier,pathao', 'string'],
            'pathao.username'      => ['required_if:courier,pathao', 'string'],
            'pathao.password'      => ['required_if:courier,pathao', 'string'],
            'pathao.base_url'      => ['nullable', 'url'],
        ];
    }
}
