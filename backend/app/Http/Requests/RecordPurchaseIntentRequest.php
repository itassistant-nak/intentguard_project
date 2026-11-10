<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPurchaseIntentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Any authenticated user (customer) may record an intent.
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
            // Merchant the intent is against
            'merchant_id'                => ['required', 'integer', 'exists:merchants,id'],

            // Core product identity
            'product_name'               => ['required', 'string', 'max:255'],
            'product_sku'                => ['nullable', 'string', 'max:128'],
            'product_url'                => ['nullable', 'url', 'max:2048'],

            // Specification: at minimum brand and condition must be provided
            'specification_json'                  => ['required', 'array', 'min:1'],
            'specification_json.brand'            => ['nullable', 'string', 'max:128'],
            'specification_json.model'            => ['nullable', 'string', 'max:128'],
            'specification_json.condition'        => ['nullable', 'string', 'in:new,refurbished,used,open_box'],
            'specification_json.colour'           => ['nullable', 'string', 'max:64'],
            'specification_json.storage'          => ['nullable', 'string', 'max:32'],
            'specification_json.*'                => ['nullable'],

            // Quantity & pricing (prices in minor currency units to avoid float issues)
            'quantity'                   => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price_cents'           => ['required', 'integer', 'min:1'],
            'currency'                   => ['required', 'string', 'size:3'],

            // Shipping terms
            'shipping_conditions_json'                      => ['required', 'array'],
            'shipping_conditions_json.method'               => ['required', 'string', 'max:128'],
            'shipping_conditions_json.estimated_days_min'   => ['required', 'integer', 'min:0'],
            'shipping_conditions_json.estimated_days_max'   => ['required', 'integer', 'min:0'],
            'shipping_conditions_json.shipping_cost_cents'  => ['required', 'integer', 'min:0'],

            // Substitution tolerances (optional — strict if omitted)
            'allowed_substitutions_json'                    => ['nullable', 'array'],
            'allowed_substitutions_json.colour_variants'    => ['nullable', 'boolean'],
            'allowed_substitutions_json.storage_upgrade'    => ['nullable', 'boolean'],
            'allowed_substitutions_json.refurbished_ok'     => ['nullable', 'boolean'],
            'allowed_substitutions_json.*'                  => ['nullable'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'merchant_id.exists'                       => 'The specified merchant does not exist.',
            'unit_price_cents.min'                     => 'The unit price must be at least 1 cent.',
            'currency.size'                            => 'Currency must be a 3-character ISO 4217 code (e.g. USD, GBP).',
            'specification_json.min'                   => 'At least one product specification attribute is required.',
            'specification_json.condition.in'          => 'Product condition must be one of: new, refurbished, used, open_box.',
        ];
    }
}
