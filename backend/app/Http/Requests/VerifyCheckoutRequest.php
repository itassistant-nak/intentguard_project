<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCheckoutRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Merchant claiming this checkout
            'merchant_id'             => ['required', 'integer', 'exists:merchants,id'],

            // Financial & scalar data
            'currency'                => ['required', 'string', 'size:3'],
            'unit_price_cents'        => ['required', 'integer', 'min:0'],
            'quantity'                => ['required', 'integer', 'min:1'],
            'total_price_cents'       => ['required', 'integer', 'min:0'],
            'shipping_cost_cents'     => ['nullable', 'integer', 'min:0'],

            // Product & spec details from merchant's checkout session
            'product_name'            => ['nullable', 'string', 'max:255'],
            'product_sku'             => ['nullable', 'string', 'max:128'],
            'specification_json'      => ['nullable', 'array'],
            'specification_json.brand'     => ['nullable', 'string', 'max:128'],
            'specification_json.model'     => ['nullable', 'string', 'max:128'],
            'specification_json.condition' => ['nullable', 'string', 'max:64'],
            'specification_json.colour'    => ['nullable', 'string', 'max:64'],
            'specification_json.storage'   => ['nullable', 'string', 'max:32'],

            // Line items array support for multi-item or detailed checkout models
            'line_items'              => ['nullable', 'array'],
            'line_items.*.product_name'      => ['nullable', 'string'],
            'line_items.*.product_sku'       => ['nullable', 'string'],
            'line_items.*.unit_price_cents'  => ['nullable', 'integer'],
            'line_items.*.quantity'          => ['nullable', 'integer'],
            'line_items.*.specification_json'=> ['nullable', 'array'],

            // Gateway reference / metadata
            'metadata'                => ['nullable', 'array'],
            'metadata.order_id'       => ['nullable', 'string', 'max:128'],
            'metadata.gateway'        => ['nullable', 'string', 'max:64'],
        ];
    }
}
