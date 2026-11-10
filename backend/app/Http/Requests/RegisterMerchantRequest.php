<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BusinessType;
use App\Enums\MerchantPlatformType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMerchantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:191'],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'business_registration_number' => ['required', 'string', 'max:64', 'unique:merchants,business_registration_number'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'business_type' => ['required', 'string', Rule::in(BusinessType::values())],
            'contact_name' => ['required', 'string', 'max:191'],
            'contact_email' => ['required', 'email', 'max:191', 'unique:merchants,contact_email'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'website_url' => ['required', 'url', 'max:255'],
            'platform_type' => ['required', 'string', Rule::in(MerchantPlatformType::values())],
        ];
    }
}
