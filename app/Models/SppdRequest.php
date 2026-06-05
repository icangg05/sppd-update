<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Vinkla\Hashids\Facades\Hashids;

class SppdRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'creator_id',
        'pptk_id',
        'budget_id',
        'category_id',
        'purpose',
        'problem',
        'facts',
        'analysis',
        'start_date',
        'end_date',
        'transport_type',
        'transport_name',
        'departure_place',
        'domain',
        'urgency',
        'status',
        'document_number',
        'notes',
        'revision_note',
        'rejection_note',
        'reviser_id',
        'sppd_date',
        'spt_date',
        'is_secretariat',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'sppd_date' => 'date',
            'spt_date' => 'date',
            'domain' => SppdDomain::class,
            'status' => SppdStatus::class,
            'is_secretariat' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'document_number'])
            ->logOnlyDirty();
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /** Pelaksana perjalanan */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Pembuat draft */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** Pemberi revisi */
    public function reviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviser_id');
    }

    /** PPTK penanggungjawab */
    public function pptk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pptk_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SppdCategory::class, 'category_id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(SppdDestination::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(SppdFollower::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SppdApproval::class)->orderBy('step_order');
    }

    public function costDetails(): HasMany
    {
        return $this->hasMany(SppdCostDetail::class);
    }

    public function actualExpenses(): HasMany
    {
        return $this->hasMany(SppdActualExpense::class);
    }

    public function advanceReceipts(): HasMany
    {
        return $this->hasMany(SppdAdvanceReceipt::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(SppdReport::class);
    }

    public function digitalSignatures(): HasMany
    {
        return $this->hasMany(SppdDigitalSignature::class);
    }

    public function signatureFor(string $documentType): ?SppdDigitalSignature
    {
        $type = $documentType;
        if ($type === 'sppd') {
            $type = 'sppd_'.$this->user_id;
        }

        $sig = $this->digitalSignatures()
            ->where('document_type', $type)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $sig && $documentType === 'sppd') {
            // Fallback to legacy 'sppd' document_type
            $sig = $this->digitalSignatures()
                ->where('document_type', 'sppd')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $sig;
    }

    public function isSigned(string $documentType): bool
    {
        return optional($this->signatureFor($documentType))->status?->value === SignatureStatus::SIGNED->value;
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Get the current active approval step.
     */
    public function currentApproval(): ?SppdApproval
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Calculate the trip duration in days.
     */
    public function durationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Accessor: $sppd->duration_days
     */
    public function getDurationDaysAttribute(): int
    {
        return $this->durationInDays();
    }

    /**
     * Get the value of the model's route key.
     */
    public function getRouteKey(): string
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (! empty($decoded)) {
            $value = $decoded[0];
        } elseif (! is_numeric($value)) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }
}
