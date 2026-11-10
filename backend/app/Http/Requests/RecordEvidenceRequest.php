<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EvidenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordEvidenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Any authenticated user may submit evidence for an intent they own.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'evidence_type' => [
                'required',
                'string',
                Rule::in(EvidenceType::values()),
            ],

            // Source URL of the page/resource snapshotted (highly recommended)
            'source_url' => ['nullable', 'url', 'max:2048'],

            // Raw content to be hashed and stored (required unless blob_path provided)
            'content' => ['required_without:blob_path', 'nullable', 'string'],

            // Blob path for externally stored large snapshots
            'blob_path' => ['required_without:content', 'nullable', 'string', 'max:1024'],

            // Pre-computed hash (required when providing blob_path without inline content)
            'content_hash' => [
                'required_if:blob_path,!null',
                'nullable',
                'string',
                'size:64',
                'regex:/^[a-f0-9]{64}$/',
            ],

            // Optional capture metadata
            'metadata' => ['nullable', 'array'],
            'metadata.user_agent'  => ['nullable', 'string', 'max:512'],
            'metadata.viewport'    => ['nullable', 'string', 'max:32'],
            'metadata.locale'      => ['nullable', 'string', 'max:16'],
            'metadata.*'           => ['nullable'],
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
            'evidence_type.in' => 'Evidence type must be one of: ' . implode(', ', EvidenceType::values()) . '.',
            'content.required_without' => 'Either content or blob_path must be provided.',
            'blob_path.required_without' => 'Either content or blob_path must be provided.',
            'content_hash.size' => 'content_hash must be a 64-character SHA-256 hex string.',
            'content_hash.regex' => 'content_hash must contain only lowercase hex characters (a-f, 0-9).',
        ];
    }
}
