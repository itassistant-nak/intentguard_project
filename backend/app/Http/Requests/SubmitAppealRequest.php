<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAppealRequest extends FormRequest
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
            'reason'              => ['required', 'string', 'min:10', 'max:3000'],
            'appeal_evidence_url' => ['nullable', 'url', 'max:1024'],
        ];
    }
}
