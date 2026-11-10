<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizePayPalOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id'                                  => ['required', 'string', 'max:64'],
            'idempotency_key'                           => ['nullable', 'string', 'max:128'],
            'checkout_payload'                          => ['required', 'array'],
            'checkout_payload.merchant_id'              => ['required', 'integer'],
            'checkout_payload.currency'                 => ['required', 'string', 'size:3'],
            'checkout_payload.unit_price_cents'         => ['required', 'integer', 'min:0'],
            'checkout_payload.quantity'                 => ['required', 'integer', 'min:1'],
            'checkout_payload.total_price_cents'        => ['required', 'integer', 'min:0'],
            'checkout_payload.shipping_cost_cents'      => ['nullable', 'integer', 'min:0'],
            'checkout_payload.product_name'             => ['nullable', 'string', 'max:255'],
            'checkout_payload.product_sku'              => ['nullable', 'string', 'max:100'],
            'checkout_payload.specification_json'       => ['nullable', 'array'],
            'checkout_payload.specification_json.brand' => ['nullable', 'string', 'max:100'],
            'checkout_payload.specification_json.model' => ['nullable', 'string', 'max:100'],
            'checkout_payload.specification_json.condition' => ['nullable', 'string', 'max:50'],
            'checkout_payload.specification_json.color'     => ['nullable', 'string', 'max:50'],
            'checkout_payload.specification_json.colour'    => ['nullable', 'string', 'max:50'],
            'checkout_payload.specification_json.storage'   => ['nullable', 'string', 'max:50'],
        ];
    }
}
