<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ComplianceEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordComplianceEventRequest extends FormRequest
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
            'event_type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($e) => $e->value, ComplianceEventType::cases())),
            ],
            'score_delta'        => ['nullable', 'numeric', 'between:-100,100'],
            'severity'           => ['nullable', 'string', Rule::in(['critical', 'major', 'minor', 'positive'])],
            'purchase_intent_id' => ['nullable', 'integer', 'exists:purchase_intents,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'evidence_reference' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
