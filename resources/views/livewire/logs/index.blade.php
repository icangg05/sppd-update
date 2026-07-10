<div class="p-1 space-y-4" x-data="{ detailOpen: false, detailId: '', detailEvent: '', detailBadgeClass: '', detailHasOld: true, detailRows: [] }">

  @php $isTte = $this->isTte(); @endphp

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 {{ $isTte ? 'bg-emerald-100 text-emerald-600' : 'bg-primary-100 text-primary-600' }} rounded">
        <i class="fa-solid {{ $isTte ? 'fa-pen-nib' : 'fa-clock-rotate-left' }} text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">{{ $isTte ? 'Logs TTE' : 'Logs Aktivitas' }}</h1>
        <p class="text-[11px] text-slate-500 font-medium">
          {{ $isTte ? 'Riwayat penandatanganan elektronik dokumen' : 'Riwayat aktivitas pengguna & perubahan data dalam sistem' }}
        </p>
      </div>
    </div>

    @if ($this->isSuperAdmin())
      <x-ui.button wire:click="confirmClear" type="button" variant="danger" :disabled="$totalLogs === 0"
        class="px-3 py-1.5 text-xs font-bold {{ $totalLogs === 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
        <i class="fa-solid fa-trash-can text-[10px]"></i> Bersihkan Log
      </x-ui.button>
    @endif
  </div>

  {{-- Filter / Pencarian --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
      <div class="relative flex-1 w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
          <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
          class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-8 text-xs focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 outline-none transition"
          placeholder="Cari deskripsi, objek, atau nama pengguna...">
        <div wire:loading wire:target="search"
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-primary-500">
          <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
        </div>
      </div>

      <x-form.searchable-select wire:model.live="event" name="event" :options="$eventOptions"
        placeholder="Semua Event" searchPlaceholder="Cari event..." wrapperClass="w-full sm:w-44" />

      @php $canReset = $search !== '' || $event !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset"
          class="px-3 py-1.5 text-xs font-medium text-slate-600 {{ $canReset ? '' : 'opacity-50 cursor-not-allowed' }}">
          <i class="fa-solid fa-rotate-right"></i> Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search,event">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-10 text-center">No.</th>
            <th class="py-2.5 px-4">Waktu</th>
            <th class="py-2.5 px-4">Pengguna</th>
            <th class="py-2.5 px-4 w-24 text-center">Event</th>
            <th class="py-2.5 px-4">Deskripsi</th>
            <th class="py-2.5 px-4">Objek</th>
            @if ($isTte)
              <th class="py-2.5 px-4 w-28 text-center">Aksi</th>
            @endif
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @php
            // Label kolom yang dapat dibaca untuk diff perubahan.
            $fieldLabels = [
              'status' => 'Status', 'document_number' => 'No. Dokumen', 'purpose' => 'Maksud',
              'start_date' => 'Tgl Mulai', 'end_date' => 'Tgl Selesai', 'departure_place' => 'Tempat Berangkat',
              'transport_type' => 'Jenis Transportasi', 'transport_name' => 'Nama Transportasi',
              'domain' => 'Wilayah', 'urgency' => 'Urgensi', 'budget_id' => 'Anggaran',
              'pptk_id' => 'PPTK', 'user_id' => 'Pegawai', 'category_id' => 'Kategori',
              'sppd_date' => 'Tgl SPPD', 'spt_date' => 'Tgl SPT', 'notes' => 'Catatan',
              'destinations' => 'Tujuan', 'followers' => 'Pengikut',
              'revision_note' => 'Catatan Revisi', 'rejection_note' => 'Catatan Penolakan',
              'is_secretariat' => 'Sekretariat', 'name' => 'Nama', 'username' => 'Username',
              'email' => 'Email', 'employee_type' => 'Tipe Pegawai', 'department_id' => 'Unit Kerja',
              'is_active' => 'Aktif',
              // Pegawai (User)
              'nip' => 'NIP', 'nik' => 'NIK', 'phone' => 'Telepon', 'rank_id' => 'Pangkat',
              'position_id' => 'Jabatan', 'dprd_jabatan' => 'Jabatan DPRD', 'partai' => 'Partai',
              // Unit Kerja (Department)
              'code' => 'Kode', 'parent_id' => 'Induk', 'head_id' => 'Kepala',
              'type' => 'Jenis', 'level' => 'Tingkat',
              // Anggaran (Budget)
              'program' => 'Program', 'activity' => 'Kegiatan', 'account_code' => 'Kode Rekening',
              'description' => 'Deskripsi', 'source' => 'Sumber', 'year' => 'Tahun',
              'total_amount' => 'Total Anggaran',
            ];
            // Format nilai penuh (untuk modal) — null jadi penanda kosong.
            // Jika field berupa foreign key, tampilkan nama relasinya, bukan id.
            $fmtFull = function ($field, $v) use ($fkConfig, $fkNames) {
              if (is_null($v) || $v === '') return '—';
              if (isset($fkConfig[$field]) && is_numeric($v)) {
                return $fkNames[$fkConfig[$field][0]][$v] ?? ('#' . $v);
              }
              if (is_bool($v)) return $v ? 'Ya' : 'Tidak';
              $v = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
              if (str_contains($v, 'T00:00:00')) $v = substr($v, 0, 10);
              return $v;
            };
          @endphp
          @forelse ($activities as $i => $activity)
            @php
              $subjectName = $activity->subject_type ? class_basename($activity->subject_type) : null;

              // Khusus SPPD: event 'updated' yang berupa penolakan/revisi ditampilkan
              // sebagai 'ditolak' / 'revisi' di kolom Event (tanpa mengubah data log).
              $displayEvent = $activity->event;
              if ($subjectName === 'SppdRequest' && $activity->event === 'updated') {
                $changedAttrs = (array) data_get($activity->attribute_changes, 'attributes', []);
                if (($changedAttrs['status'] ?? null) === 'rejected') {
                  $displayEvent = 'ditolak';
                } elseif (! empty($changedAttrs['revision_note'])) {
                  $displayEvent = 'revisi';
                }
              }

              $eventColor = match ($displayEvent) {
                'created', 'signed' => 'green',
                'updated'           => 'amber',
                'revisi'            => 'orange',
                'deleted', 'rejected', 'ditolak' => 'rose',
                default             => 'slate',
              };
              // Kelas pill badge (untuk dipakai di modal lewat Alpine).
              $eventBadgeClass = match ($eventColor) {
                'green'  => 'bg-green-50 text-green-700 ring-green-600/20',
                'amber'  => 'bg-amber-50 text-amber-800 ring-amber-600/20',
                'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                'rose'   => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                default  => 'bg-slate-50 text-slate-700 ring-slate-600/20',
              };

              // Ganti deskripsi default mentah (mis. "deleted") menjadi frasa Indonesia.
              $subjectLabelId = match ($subjectName) {
                'User'        => 'Pengguna',
                'SppdRequest' => 'SPPD',
                'Department'  => 'Unit Kerja',
                'Budget'      => 'Anggaran',
                'Role'        => 'Role',
                default       => $subjectName ? \Illuminate\Support\Str::headline($subjectName) : 'Data',
              };
              $eventVerbId = match ($activity->event) {
                'created'  => 'dibuat',
                'updated'  => 'diperbarui',
                'deleted'  => 'dihapus',
                'signed'   => 'ditandatangani',
                'rejected' => 'ditolak',
                default    => $activity->event,
              };
              // Nama entitas: dari subjek bila masih ada, jika tidak ambil dari diff.
              $ch0 = $activity->attribute_changes;
              $entityName = (is_object($activity->subject) && ! empty($activity->subject->name))
                ? $activity->subject->name
                : (data_get($ch0, 'attributes.name') ?? data_get($ch0, 'old.name'));

              $descRaw = trim((string) $activity->description);
              $descText = mb_strtolower($descRaw) === mb_strtolower((string) $activity->event)
                ? trim($entityName ? "{$subjectLabelId} {$entityName} {$eventVerbId}" : "{$subjectLabelId} {$eventVerbId}")
                : $descRaw;

              // Tebalkan bagian nama di tengah: "{label} **{nama}** {kata kerja}".
              $descHtml = e($descText);
              if ($eventVerbId && str_starts_with($descText, $subjectLabelId . ' ') && str_ends_with($descText, ' ' . $eventVerbId)) {
                $mid = trim(mb_substr($descText, mb_strlen($subjectLabelId), mb_strlen($descText) - mb_strlen($subjectLabelId) - mb_strlen($eventVerbId)));
                if ($mid !== '') {
                  $descHtml = e($subjectLabelId) . ' <strong class="font-bold text-slate-800">' . e($mid) . '</strong> ' . e($eventVerbId);
                }
              }
            @endphp
            <tr wire:key="log-{{ $activity->id }}" class="hover:bg-slate-50/50 transition-colors align-top">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">{{ $activities->firstItem() + $i }}.</td>

              <td class="py-2.5 px-4">
                <div class="font-semibold text-slate-800">{{ $activity->created_at->translatedFormat('d M Y') }}</div>
                <div class="text-[11px] text-slate-500">{{ $activity->created_at->format('H:i:s') }} &middot; {{ $activity->created_at->diffForHumans() }}</div>
              </td>

              <td class="py-2.5 px-4">
                @if ($activity->causer)
                  <div class="font-semibold text-slate-800">{{ $activity->causer->name }}</div>
                  <div class="text-[11px] text-slate-500">{{ $activity->causer->username ?? '' }}</div>
                @else
                  <span class="text-slate-500 italic text-[11px]">Sistem</span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                @if ($activity->event)
                  <x-ui.badge :color="$eventColor" class="uppercase tracking-wide">{{ $displayEvent }}</x-ui.badge>
                @else
                  <span class="text-slate-500">-</span>
                @endif
              </td>

              <td class="py-2.5 px-4 whitespace-normal max-w-md">
                <span class="text-slate-700">{!! $descHtml !!}</span>

                @php
                  $sigId = data_get($activity->properties, 'signature_id');
                  $signedUrl = data_get($activity->properties, 'signed_file_url')
                    ?? ($signedUrls[$sigId] ?? null);

                  // Diff perubahan atribut (kolom attribute_changes).
                  $changes  = $activity->attribute_changes;
                  $newAttrs = (array) data_get($changes, 'attributes', []);
                  $oldAttrs = (array) data_get($changes, 'old', []);
                  $changedKeys = array_values(array_unique(array_merge(array_keys($newAttrs), array_keys($oldAttrs))));

                  // Bangun baris terstruktur untuk modal (label, sebelum, sesudah).
                  $detailRows = [];
                  $detailHasOld = $activity->event !== 'created';
                  if (! empty($changedKeys)) {
                    foreach ($changedKeys as $key) {
                      $detailRows[] = [
                        'label' => $fieldLabels[$key] ?? \Illuminate\Support\Str::headline($key),
                        'old'   => $fmtFull($key, $oldAttrs[$key] ?? null),
                        'new'   => $fmtFull($key, $newAttrs[$key] ?? null),
                      ];
                    }
                  } elseif ($activity->properties && $activity->properties->isNotEmpty()) {
                    // Log TTE / properti datar: tampilkan sebagai label + nilai.
                    $detailHasOld = false;
                    foreach ($activity->properties as $k => $v) {
                      $detailRows[] = [
                        'label' => $fieldLabels[$k] ?? \Illuminate\Support\Str::headline($k),
                        'old'   => '—',
                        'new'   => $fmtFull($k, $v),
                      ];
                    }
                  }
                  $changedCount = count($detailRows);
                @endphp

                @if ($changedCount > 0 || $signedUrl)
                  <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                    @if ($changedCount > 0)
                      <button type="button"
                        @click="detailRows = @js($detailRows); detailHasOld = @js($detailHasOld); detailId = @js('#' . $activity->id); detailEvent = @js(strtoupper($displayEvent ?? 'LOG')); detailBadgeClass = @js($eventBadgeClass); detailOpen = true"
                        class="inline-flex items-center gap-1.5 rounded bg-primary-50 px-2 py-0.5 text-[11px] font-bold text-primary-700 transition hover:bg-primary-100">
                        <i class="fa-solid fa-table-list text-[10px]"></i> Detail perubahan
                        <span class="rounded bg-primary-200/60 px-1 text-[10px]">{{ $changedCount }}</span>
                      </button>
                    @endif

                    @if ($signedUrl)
                      <a href="{{ $signedUrl }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100"
                        title="Buka dokumen yang ditandatangani di tab baru">
                        <i class="fa-solid fa-file-signature text-[10px]"></i> Lihat dokumen
                        <i class="fa-solid fa-up-right-from-square text-[8px]"></i>
                      </a>
                    @endif
                  </div>
                @endif
              </td>

              <td class="py-2.5 px-4">
                @if ($subjectName)
                  <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-mono text-slate-700">{{ $subjectName }}</code>
                  @if ($activity->subject_id)
                    <span class="text-[11px] text-slate-500">#{{ $activity->subject_id }}</span>
                  @endif
                  @if (! $activity->subject)
                    <span class="ml-1 inline-flex items-center rounded bg-rose-50 px-1 py-0.5 text-[9px] font-bold text-rose-600 uppercase">Dihapus</span>
                  @endif
                @else
                  <span class="text-slate-500">-</span>
                @endif
              </td>

              @if ($isTte)
                <td class="py-2.5 px-4 text-center">
                  @if ($activity->subject && $activity->subject_type === \App\Models\SppdRequest::class)
                    <a href="{{ route('sppd.show', $activity->subject_id) }}" target="_blank" rel="noopener noreferrer"
                      class="inline-flex items-center justify-center rounded border border-emerald-200 bg-emerald-50 p-1.5 text-emerald-700 transition hover:bg-emerald-100"
                      title="Buka detail SPPD di tab baru">
                      <i class="fa-solid fa-up-right-from-square text-[11px]"></i>
                    </a>
                  @else
                    <span class="text-slate-300">&mdash;</span>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isTte ? 7 : 6 }}" class="py-10 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid {{ $isTte ? 'fa-pen-nib' : 'fa-clock-rotate-left' }} text-2xl opacity-40"></i>
                  <p class="font-medium">{{ $isTte ? 'Belum ada riwayat penandatanganan elektronik' : 'Belum ada aktivitas yang tercatat' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($activities->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $activities->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Detail Perubahan — tabel sebelum → sesudah --}}
  <x-ui.modal show="detailOpen" title="Detail Perubahan" icon="fa-solid fa-table-list text-primary-600"
    maxWidth="max-w-3xl" :closeable="true">
    <div class="mb-3 flex items-center gap-2">
      <span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-medium uppercase tracking-wide ring-1 ring-inset"
        :class="detailBadgeClass" x-text="detailEvent"></span>
      <span class="text-[11px] font-semibold text-slate-500" x-text="detailId"></span>
    </div>

    <div class="max-h-[60vh] overflow-auto rounded-lg border border-slate-200">
      <table class="w-full border-collapse text-left text-xs">
        <thead class="sticky top-0 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
          <tr>
            <th class="border-b border-slate-200 px-3 py-2 w-40">Field</th>
            <th class="border-b border-slate-200 px-3 py-2" x-show="detailHasOld">Sebelum</th>
            <th class="border-b border-slate-200 px-3 py-2" x-text="detailHasOld ? 'Sesudah' : 'Nilai'"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="(row, idx) in detailRows" :key="idx">
            <tr class="align-top">
              <td class="px-3 py-2 font-semibold text-slate-600" x-text="row.label"></td>
              <td class="px-3 py-2" x-show="detailHasOld">
                <span class="inline-block rounded bg-rose-50 px-1.5 py-0.5 text-rose-600"
                  :class="row.old === '—' && 'bg-transparent text-slate-300'"
                  x-text="row.old"></span>
              </td>
              <td class="px-3 py-2">
                <span class="inline-block rounded bg-emerald-50 px-1.5 py-0.5 font-medium text-emerald-700"
                  :class="row.new === '—' && 'bg-transparent text-slate-300'"
                  x-text="row.new"></span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end pt-3">
      <x-ui.button type="button" variant="secondary" @click="detailOpen = false">Tutup</x-ui.button>
    </div>
  </x-ui.modal>

  {{-- Modal Konfirmasi Bersihkan Log — tombol Hapus aktif setelah 10 detik --}}
  <x-ui.modal show="$wire.showClearModal" title="{{ $isTte ? 'Bersihkan Semua Log TTE' : 'Bersihkan Semua Log' }}"
    description="{{ $isTte ? 'Seluruh riwayat penandatanganan elektronik akan dihapus permanen' : 'Seluruh riwayat aktivitas akan dihapus permanen' }}"
    icon="fa-solid fa-trash-can text-rose-600" :closeable="false">
    <div class="space-y-4"
      x-data="{
        remaining: 10,
        timer: null,
        startCountdown() {
          this.remaining = 10;
          clearInterval(this.timer);
          this.timer = setInterval(() => {
            if (this.remaining > 0) this.remaining--;
            if (this.remaining <= 0) clearInterval(this.timer);
          }, 1000);
        },
      }"
      x-on:log-clear-countdown.window="startCountdown()"
      x-init="if ($wire.showClearModal) startCountdown()">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus
        <span class="font-bold text-slate-800">{{ $isTte ? 'seluruh catatan TTE' : 'seluruh catatan aktivitas' }}</span>?
        Tindakan ini tidak dapat dibatalkan dan seluruh data log akan hilang permanen.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeClearModal">Tutup</x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="clearLogs"
          x-bind:disabled="remaining > 0"
          x-bind:class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''">
          <span x-show="remaining > 0"><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="remaining"></span>s</span>
          <span x-show="remaining <= 0" x-cloak>
            <span wire:loading.remove wire:target="clearLogs"><i class="fa-solid fa-trash-can"></i> Hapus Semua</span>
            <span wire:loading wire:target="clearLogs"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
          </span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

</div>
