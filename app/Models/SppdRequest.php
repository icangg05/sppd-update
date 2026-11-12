<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\SignatureStatus;
use App\Helpers\SmartTitle;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Services\SppdWorkflowService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
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
            ->logOnly([
                'status',
                'document_number',
                'purpose',
                'start_date',
                'end_date',
                'departure_place',
                'transport_type',
                'transport_name',
                'domain',
                'urgency',
                'budget_id',
                'pptk_id',
                'user_id',
                'category_id',
                'sppd_date',
                'spt_date',
                'notes',
                'revision_note',
                'rejection_note',
                'is_secretariat',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event) => $this->describeLogEvent($event));
    }

    /**
     * Deskripsi aktivitas yang mudah dibaca (mis. "SPPD 090/123 diperbarui").
     */
    protected function describeLogEvent(string $event): string
    {
        $label = $this->document_number ? "SPPD {$this->document_number}" : "SPPD #{$this->getKey()}";

        return match ($event) {
            'created' => "{$label} dibuat",
            'updated' => "{$label} diperbarui",
            'deleted' => "{$label} dihapus",
            default   => "{$label} {$event}",
        };
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

    /**
     * Nomor dokumen ditampilkan (termasuk keterangan "belum bernomor") bila sudah ada,
     * atau bila pelaksananya dari instansi yang menginput nomor sendiri (Inspektorat).
     */
    public function showsDocumentNumber(): bool
    {
        return filled($this->document_number) || (bool) $this->user?->department?->usesManualNumbering();
    }

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

    /**
     * Pengikut untuk dokumen SPT: jabatan perjalanan tertinggi dulu (Inspektorat, lihat
     * SppdFollower::TRAVEL_POSITIONS), lalu jabatan struktural tertinggi (level kecil),
     * lalu golongan tertinggi; sisanya tetap sesuai urutan input.
     */
    public function followersForSpt(): Collection
    {
        return $this->followers
            ->sortBy([
                fn ($a, $b) => SppdFollower::travelPositionRank($a->travel_position)
                    <=> SppdFollower::travelPositionRank($b->travel_position),
                fn ($a, $b) => ($a->user?->position?->level ?? Position::DEFAULT_LEVEL)
                    <=> ($b->user?->position?->level ?? Position::DEFAULT_LEVEL),
                fn ($a, $b) => ($b->user?->rank?->weight() ?? 0) <=> ($a->user?->rank?->weight() ?? 0),
            ])
            ->values();
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

    /**
     * Reset TTE saat SPPD direvisi: dokumennya akan berubah, jadi tanda tangan lama (beserta
     * file bertanda tangannya, juga yang gagal) tidak berlaku lagi. Tahap penanda tangan yang
     * sudah menyetujui dikembalikan ke menunggu agar menandatangani ulang hasil perbaikan.
     */
    public function resetDigitalSignatures(): void
    {
        $disk = Storage::disk(config('tte.storage.disk'));
        foreach ($this->digitalSignatures()->get() as $signature) {
            if ($signature->signed_file_path && $disk->exists($signature->signed_file_path)) {
                $disk->delete($signature->signed_file_path);
            }
        }
        $this->digitalSignatures()->delete();

        $this->approvals()
            ->where('status', ApprovalStatus::APPROVED)
            ->where(fn ($q) => $q->where('signs_spt', true)->orWhere('signs_sppd', true))
            ->update(['status' => ApprovalStatus::PENDING, 'acted_at' => null, 'notes' => null]);
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

    /** SPPD yang masih berjalan: dalam proses, atau disetujui dan periodenya belum lewat. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('status', SppdStatus::IN_PROGRESS)
            ->orWhere(fn ($approved) => $approved
                ->where('status', SppdStatus::APPROVED)
                ->whereDate('end_date', '>=', today())));
    }

    /**
     * SPPD yang memakai tanggal pesertanya: semua status kecuali ditolak, termasuk yang sudah
     * selesai, karena orang yang sama tidak mungkin bertugas di dua tempat pada hari yang sama.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', '!=', SppdStatus::REJECTED);
    }

    /** SPPD terjadwal (lihat scopeScheduled) yang periodenya beririsan dengan $start s/d $end (inklusif). */
    public function scopeOverlapping(Builder $query, CarbonInterface|string $start, CarbonInterface|string $end): Builder
    {
        return $query->scheduled()
            ->whereDate('start_date', '<=', Carbon::parse($end)->toDateString())
            ->whereDate('end_date', '>=', Carbon::parse($start)->toDateString());
    }

    /**
     * SPPD aktif tiap pegawai, urut tanggal berangkat. Pegawai boleh punya beberapa SPPD
     * aktif selama tanggalnya tidak bentrok (lihat clashingForUsers); daftar ini hanya
     * ditampilkan sebagai acuan saat memilih tanggal.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, Collection<int, self>> pegawai tanpa SPPD aktif tidak ada di hasil.
     */
    public static function activeForUsers(Collection $users, ?int $exceptId = null): array
    {
        return static::tripsOfUsers($users, static::active(), $exceptId);
    }

    /**
     * Semua SPPD terjadwal tiap pegawai (lihat scopeScheduled), urut tanggal berangkat:
     * tanggalnya dinonaktifkan di kalender agar tidak bisa dipilih.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, Collection<int, self>> pegawai tanpa SPPD tidak ada di hasil.
     */
    public static function scheduledForUsers(Collection $users, ?int $exceptId = null): array
    {
        return static::tripsOfUsers($users, static::scheduled(), $exceptId);
    }

    /**
     * SPPD yang tanggalnya bentrok dengan perjalanan $start s/d $end, satu per pegawai
     * (yang berangkat paling awal). Inilah syarat seseorang bisa menjadi pelaksana atau
     * pengikut: SPT/SPPD boleh dibuat jauh hari karena persetujuan TTE memakan waktu.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, self> pegawai yang bebas pada tanggal itu tidak ada di hasil.
     */
    public static function clashingForUsers(Collection $users, CarbonInterface|string $start, CarbonInterface|string $end, ?int $exceptId = null): array
    {
        return array_map(
            fn (Collection $trips) => $trips->first(),
            static::tripsOfUsers($users, static::overlapping($start, $end), $exceptId),
        );
    }

    /**
     * SPPD tiap pegawai, sebagai pelaksana maupun pengikut, lewat akun mana pun miliknya.
     * Pegawai yang bertugas di dua instansi punya satu akun per instansi dengan NIP yang
     * sama. NIK sengaja tidak dipakai karena banyak data lama memuat NIK sama untuk orang
     * berbeda.
     *
     * @param  Collection<int, User>  $users
     * @param  Builder<self>  $query
     * @return array<int, Collection<int, self>>
     */
    private static function tripsOfUsers(Collection $users, Builder $query, ?int $exceptId): array
    {
        $ownersByAccount = $users->mapWithKeys(fn (User $user) => [$user->id => [$user->id]])->all();

        $holders = $users->filter(fn (User $user) => filled($user->nip))->groupBy('nip');
        if ($holders->isNotEmpty()) {
            // Kunci array PHP mengubah NIP menjadi angka, padahal MySQL membandingkan
            // angka 18 digit secara tidak presisi; jadi dikirim kembali sebagai teks.
            $nips = $holders->keys()->map(fn ($nip) => (string) $nip)->all();

            foreach (User::whereIn('nip', $nips)->get(['id', 'nip']) as $account) {
                $ownersByAccount[$account->id] = [
                    ...($ownersByAccount[$account->id] ?? []),
                    ...$holders[$account->nip]->pluck('id'),
                ];
            }
        }

        $accountIds = array_keys($ownersByAccount);
        if ($accountIds === []) {
            return [];
        }

        $trips = $query
            ->with(['user.department.parent', 'followers:id,sppd_request_id,user_id'])
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->where(fn ($q) => $q
                ->whereIn('user_id', $accountIds)
                ->orWhereHas('followers', fn ($f) => $f->whereIn('user_id', $accountIds)))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $result = [];
        foreach ($trips as $sppd) {
            foreach ([$sppd->user_id, ...$sppd->followers->pluck('user_id')] as $participantId) {
                foreach ($ownersByAccount[$participantId] ?? [] as $ownerId) {
                    $result[$ownerId][$sppd->id] = $sppd;
                }
            }
        }

        return array_map(fn (array $byId) => collect(array_values($byId)), $result);
    }

    /** Nama instansi pelaksana SPPD ini, mis. "Dinas Kominfo" untuk pelaksana di salah satu Bidang-nya. */
    public function instansiName(): string
    {
        return app(SppdWorkflowService::class)->instansiOf($this->user)?->name ?? 'unit kerja lain';
    }

    /** Periode perjalanan, mis. "12–15 November 2026", "28 November – 02 Desember 2026", atau satu tanggal. */
    public function periodLabel(bool $shortMonth = false): string
    {
        $month = $shortMonth ? 'M' : 'F';
        $start = $this->start_date;
        $end = $this->end_date;

        return match (true) {
            $start->isSameDay($end) => $start->translatedFormat("d {$month} Y"),
            $start->isSameMonth($end) => $start->format('d') . '–' . $end->translatedFormat("d {$month} Y"),
            $start->isSameYear($end) => $start->translatedFormat("d {$month}") . ' – ' . $end->translatedFormat("d {$month} Y"),
            default => $start->translatedFormat("d {$month} Y") . ' – ' . $end->translatedFormat("d {$month} Y"),
        };
    }

    /** Tahap SPPD ini dalam kalimat, mis. "dalam proses persetujuan". */
    public function statusPhrase(): string
    {
        return match ($this->status) {
            SppdStatus::IN_PROGRESS => 'dalam proses persetujuan',
            SppdStatus::APPROVED => 'sudah disetujui',
            SppdStatus::COMPLETED => 'sudah selesai',
            default => strtolower($this->status?->label() ?? ''),
        };
    }

    /** Alasan pesertanya tidak bisa bepergian pada tanggal yang dipilih, disambung setelah nama pegawai. */
    public function clashMessage(): string
    {
        return "sudah memiliki SPPD di {$this->instansiName()} pada {$this->periodLabel()} ({$this->statusPhrase()}) yang bentrok dengan tanggal perjalanan ini.";
    }

    /**
     * Lama perjalanan dalam hari. Dalam daerah tidak menghitung Sabtu & Minggu
     * (lihat SppdDomain::travelDays).
     */
    public function durationInDays(): int
    {
        return $this->domain
            ? $this->domain->travelDays($this->start_date, $this->end_date)
            : (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Tempat berangkat diketik bebas: huruf pertama tiap kata selalu kapital. */
    public function getDeparturePlaceAttribute(?string $value): ?string
    {
        return SmartTitle::capitalizeWords($value);
    }

    public function setDeparturePlaceAttribute(?string $value): void
    {
        $this->attributes['departure_place'] = SmartTitle::capitalizeWords($value);
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
     * Token rahasia untuk tautan publik (read-only) SPPD. Diturunkan dari
     * app.key sehingga tidak dapat ditebak walau id/hashids-nya diketahui.
     */
    public function publicToken(): string
    {
        return hash_hmac('sha256', 'sppd-public|' . $this->getKey(), config('app.key'));
    }

    /**
     * URL halaman publik (read-only) SPPD, lengkap dengan token.
     */
    public function publicUrl(): string
    {
        return route('sppd.public', ['sppd' => $this, 'token' => $this->publicToken()]);
    }

    /**
     * Lampiran berupa gambar (dilihat lewat galeri), bukan PDF/Word.
     */
    public function attachmentIsImage(): bool
    {
        $extension = strtolower(pathinfo((string) $this->attachment, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
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
