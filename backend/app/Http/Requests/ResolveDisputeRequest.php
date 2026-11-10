<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DisputeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    DisputeStatus::RESOLVED_REFUNDED->value,
                    DisputeStatus::RESOLVED_REPLACED->value,
                    DisputeStatus::RESOLVED_REJECTED->value,
                ]),
            ],
            'notes' => ['required', 'string', 'min:5', 'max:3000'],
        ];
    }
}
