<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
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
        'attachment',
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

    protected static function booted(): void
    {
        static::deleting(function (SppdRequest $sppd) {
            // Delete attachments
            if ($sppd->attachment && Storage::disk('public')->exists($sppd->attachment)) {
                Storage::disk('public')->delete($sppd->attachment);
            }

            // Delete digital signatures signed files
            $signatures = $sppd->digitalSignatures()->get();
            $ttesDisk = Storage::disk(config('tte.storage.disk'));
            $draftDir = config('tte.storage.paths.draft');

            foreach ($signatures as $sig) {
                if ($sig->signed_file_path && $ttesDisk->exists($sig->signed_file_path)) {
                    $ttesDisk->delete($sig->signed_file_path);
                }
            }

            // Delete draft files
            if ($draftDir && $ttesDisk->exists($draftDir)) {
                foreach ($ttesDisk->files($draftDir) as $file) {
                    if (str_contains(basename($file), (string)$sppd->id)) {
                        $ttesDisk->delete($file);
                    }
                }
            }

            // Delete report files
            $report = $sppd->report;
            if ($report) {
                if ($report->report_file && Storage::disk('public')->exists($report->report_file)) {
                    Storage::disk('public')->delete($report->report_file);
                }
                if ($report->documentation_file && Storage::disk('public')->exists($report->documentation_file)) {
                    Storage::disk('public')->delete($report->documentation_file);
                }
            }

            // Delete cost details receipt photos
            foreach ($sppd->costDetails as $cost) {
                if ($cost->receipt_photo && Storage::disk('public')->exists($cost->receipt_photo)) {
                    Storage::disk('public')->delete($cost->receipt_photo);
                }
            }

            // Delete actual expenses receipt files
            foreach ($sppd->actualExpenses as $expense) {
                if ($expense->receipt_file && Storage::disk('public')->exists($expense->receipt_file)) {
                    Storage::disk('public')->delete($expense->receipt_file);
                }
            }

            // Delete advance receipts receipt files
            foreach ($sppd->advanceReceipts as $receipt) {
                if ($receipt->receipt_file && Storage::disk('public')->exists($receipt->receipt_file)) {
                    Storage::disk('public')->delete($receipt->receipt_file);
                }
            }
        });
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
