<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DisputeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dispute_category' => [
                'required',
                'string',
                Rule::in(array_map(fn ($c) => $c->value, DisputeCategory::cases())),
            ],
            'description'          => ['required', 'string', 'min:10', 'max:5000'],
            'claimed_amount_cents' => ['nullable', 'integer', 'min:0'],
            'evidence_snapshot_id' => ['nullable', 'integer', 'exists:purchase_evidence,id'],
        ];
    }
}
