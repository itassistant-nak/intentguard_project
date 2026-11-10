<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EvidenceType;
use Database\Factories\PurchaseEvidenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable evidence record — the chain-of-custody snapshot for a purchase intent.
 *
 * IMPORTANT: Records in this table are APPEND-ONLY. Never update or delete.
 * The integrity of this model is enforced by content_hash (SHA-256).
 */
class PurchaseEvidence extends Model
{
    /** @use HasFactory<PurchaseEvidenceFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'purchase_evidence';

    /**
     * Disable updated_at — evidence records are immutable.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_intent_id',
        'evidence_type',
        'source_url',
        'content_snapshot',
        'content_snapshot_blob_path',
        'content_hash',
        'metadata_json',
        'submitted_by',
        'captured_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence_type' => EvidenceType::class,
            'metadata_json' => 'array',
            'captured_at'   => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The purchase intent this evidence belongs to.
     */
    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class);
    }

    /**
     * The user who submitted this evidence (customer or system actor).
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // -------------------------------------------------------------------------
    // Business logic helpers
    // -------------------------------------------------------------------------

    /**
     * Verifies that the stored content_hash matches a fresh SHA-256 of content_snapshot.
     * Returns false if content_snapshot is null or the hash does not match (tamper detected).
     */
    public function verifyContentIntegrity(): bool
    {
        if ($this->content_snapshot === null) {
            return false;
        }

        return hash_equals(
            $this->content_hash,
            hash('sha256', $this->content_snapshot),
        );
    }

    /**
     * Whether this evidence record has an inline content snapshot.
     */
    public function hasInlineSnapshot(): bool
    {
        return $this->content_snapshot !== null;
    }

    /**
     * Whether this evidence record references a blob-stored snapshot.
     */
    public function hasBlobSnapshot(): bool
    {
        return $this->content_snapshot_blob_path !== null;
    }
}
