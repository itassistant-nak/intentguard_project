<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptAgreementRequest extends FormRequest
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
            'agreement_id' => ['required', 'integer', 'exists:merchant_agreements,id'],
            'signatory_name' => ['required', 'string', 'min:2', 'max:191'],
            'signatory_title' => ['required', 'string', 'min:2', 'max:100'],
            'terms_digest_snapshot' => ['required', 'string', 'size:64'],
        ];
    }
}
