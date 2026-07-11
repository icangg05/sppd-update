<div class="p-1 space-y-4"
  x-data
  x-init="if (window.location.hash === '#ajukan-jabatan') {
    $wire.openCreateModal();
    // Hapus fragment agar modal tidak terbuka lagi saat refresh / tombol back.
    history.replaceState(null, '', window.location.pathname + window.location.search);
  }">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid {{ $isSuperAdmin ? 'fa-gavel' : 'fa-id-badge' }} pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid {{ $isSuperAdmin ? 'fa-clipboard-check' : 'fa-paper-plane' }} text-[9px]"></i>
          {{ $isSuperAdmin ? 'Verifikasi Usulan' : 'Usulan Jabatan' }}
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $requests->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Pengajuan Jabatan</h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ $isSuperAdmin
            ? 'Verifikasi usulan jabatan baru dari Admin OPD dan pastikan tidak duplikat'
            : 'Ajukan jabatan baru jika belum tersedia, lalu menunggu verifikasi Super Admin' }}
        </p>
      </div>

      <x-ui.button type="button" wire:click="openCreateModal" variant="primary" class="shrink-0 font-bold">
        <i class="fa-solid fa-plus text-xs"></i>
        Ajukan Jabatan
      </x-ui.button>
    </div>
  </div>

  {{-- Keterangan: pengajuan menunggu persetujuan administrator sistem (Admin OPD) --}}
  @unless($isSuperAdmin)
    <div class="dash-enter flex items-start gap-3 rounded border border-amber-200 bg-amber-50/70 px-4 py-3 text-amber-900">
      <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 ring-1 ring-amber-200">
        <i class="fa-solid fa-circle-info text-xs"></i>
      </div>
      <p class="text-xs leading-relaxed">
        Setiap jabatan yang Anda ajukan berstatus
        <span class="font-semibold">menunggu persetujuan administrator sistem</span>
        sebelum dapat digunakan. Pantau perkembangannya melalui kolom <span class="font-semibold">Status</span> di bawah.
      </p>
    </div>
  @endunless

  {{-- Filter --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-4">
    <div class="flex flex-col gap-3 sm:flex-row">
      <x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" wrapperClass="flex-1 w-full"
        placeholder="Cari nama jabatan..." />

      @php
        $statusOptions = collect($statuses)
          ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
          ->prepend(['value' => '', 'label' => 'Semua Status'])
          ->all();
      @endphp
      <div class="w-full sm:w-44">
        <x-form.searchable-select wire:model.live="statusFilter" name="statusFilter" :options="$statusOptions"
          placeholder="Semua Status" searchPlaceholder="Cari status..." />
      </div>

      @php $canReset = $search !== '' || $statusFilter !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset">
          <x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
          Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Tabel --}}
  <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search,statusFilter">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No.</th>
            <th class="py-2.5 px-4">Nama Jabatan</th>
            <th class="py-2.5 px-4">Alasan</th>
            @if($isSuperAdmin)
              <th class="py-2.5 px-4">Pengusul</th>
              <th class="py-2.5 px-4">OPD</th>
            @endif
            <th class="py-2.5 px-4 w-28">Status</th>
            <th class="py-2.5 px-4 w-36 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse($requests as $i => $req)
            <tr wire:key="req-{{ $req->id }}" class="transition-colors hover:bg-slate-50/50">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">
                {{ $requests->firstItem() + $i }}.
              </td>
              <td class="py-2.5 px-4 font-semibold text-slate-900">{{ $req->name }}</td>
              <td class="py-2.5 px-4 text-[11px] max-w-xs">
                @if($req->status === \App\Enums\PositionRequestStatus::REJECTED && $req->review_note)
                  <span class="font-semibold text-rose-600">Ditolak:</span>
                  <span class="text-rose-500 whitespace-normal" title="{{ $req->review_note }}">{{ $req->review_note }}</span>
                @elseif($req->status === \App\Enums\PositionRequestStatus::APPROVED && $req->review_note)
                  <span class="font-semibold text-emerald-600">Catatan:</span>
                  <span class="text-emerald-600 whitespace-normal" title="{{ $req->review_note }}">{{ $req->review_note }}</span>
                @else
                  <span class="text-slate-500 block truncate" title="{{ $req->reason }}">{{ $req->reason ?? '—' }}</span>
                @endif
              </td>
              @if($isSuperAdmin)
                <td class="py-2.5 px-4 text-slate-600 font-medium">{{ $req->requester?->name ?? '—' }}</td>
                <td class="py-2.5 px-4 text-slate-500 text-[11px] truncate max-w-40">{{ $req->department?->name ?? '—' }}</td>
              @endif
              <td class="py-2.5 px-4">
                <x-ui.badge :color="$req->status->color()">{{ $req->status->label() }}</x-ui.badge>
              </td>
              @php
                $isPending  = $req->status === \App\Enums\PositionRequestStatus::PENDING;
                $canManage  = $isSuperAdmin || $req->requested_by === auth()->id();
                $canEdit    = $canManage && $isPending;
                $canDelete  = $canManage;
              @endphp
              <td class="py-2.5 px-4">
                <div class="flex items-center justify-center gap-1.5">
                  @if($isSuperAdmin && $isPending)
                    <button type="button" wire:click="openVerifyModal({{ $req->id }})"
                      class="inline-flex items-center gap-1 rounded border border-primary-200 bg-primary-50 px-2 py-1 text-[11px] font-semibold text-primary-700 transition hover:bg-primary-100 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40"
                      title="Verifikasi">
                      <i class="fa-solid fa-gavel text-[10px]"></i> Verifikasi
                    </button>
                  @endif

                  @if($canEdit)
                    <button type="button" wire:click="openEditModal({{ $req->id }})"
                      class="inline-flex items-center justify-center rounded border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40"
                      title="Edit">
                      <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                    </button>
                  @endif

                  @if($canDelete)
                    <button type="button" wire:click="confirmDelete({{ $req->id }})"
                      class="inline-flex items-center justify-center rounded border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-100 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40"
                      title="Hapus">
                      <i class="fa-solid fa-trash-can text-[10px]"></i>
                    </button>
                  @endif

                  @unless(($isSuperAdmin && $isPending) || $canEdit || $canDelete)
                    <span class="text-slate-300">—</span>
                  @endunless
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isSuperAdmin ? '7' : '5' }}" class="px-4 py-16">
                <div class="mx-auto flex max-w-sm flex-col items-center text-center">
                  <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                    <i class="fa-solid fa-id-badge text-xl"></i>
                  </div>

                  @if($canReset)
                    <p class="mt-4 text-sm font-semibold text-slate-700">Tidak ada pengajuan yang cocok</p>
                    <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci atau hapus filter status yang aktif.</p>
                    <x-ui.button wire:click="resetFilters" type="button" variant="secondary" size="sm" class="mt-4">
                      <i class="fa-solid fa-rotate-right text-xs"></i> Reset filter
                    </x-ui.button>
                  @else
                    <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada pengajuan jabatan</p>
                    <p class="mt-1 text-xs text-slate-500">
                      {{ $isSuperAdmin
                        ? 'Usulan jabatan dari Admin OPD akan muncul di sini untuk diverifikasi.'
                        : 'Ajukan jabatan baru bila jabatan yang Anda butuhkan belum tersedia.' }}
                    </p>
                    @unless($isSuperAdmin)
                      <x-ui.button type="button" wire:click="openCreateModal" variant="primary" size="sm" class="mt-4">
                        <i class="fa-solid fa-plus text-xs"></i> Ajukan Jabatan
                      </x-ui.button>
                    @endunless
                  @endif
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($requests->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $requests->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Ajukan / Edit Jabatan --}}
  <x-ui.modal show="$wire.showCreateModal" :title="$editingId ? 'Edit Pengajuan Jabatan' : 'Ajukan Jabatan Baru'"
    :description="$editingId ? 'Perbarui usulan jabatan yang masih menunggu' : 'Usulkan jabatan yang belum tersedia'"
    icon="fa-solid fa-id-badge text-primary-600" :closeable="false">
    <form wire:submit="submit" class="space-y-4">
      <x-form.input name="name" label="Nama Jabatan" wire:model="name" required
        placeholder="Contoh: Analis Sumber Daya Manusia Aparatur"
        hint="Cakupan keunikan (boleh diisi banyak / satu per OPD / satu sistem) ditetapkan Super Admin saat verifikasi." />

      <x-form.textarea name="reason" label="Alasan / Keterangan" wire:model="reason" rows="3"
        placeholder="Jelaskan kebutuhan jabatan ini (opsional)." />

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" x-on:click="$wire.showCreateModal = false">Batal</x-ui.button>
        <x-ui.button type="submit">
          @if($editingId)
            <span wire:loading.remove wire:target="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</span>
            <span wire:loading wire:target="submit"><i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...</span>
          @else
            <span wire:loading.remove wire:target="submit"><i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan</span>
            <span wire:loading wire:target="submit"><i class="fa-solid fa-spinner fa-spin"></i> Mengirim...</span>
          @endif
        </x-ui.button>
      </div>
    </form>
  </x-ui.modal>

  {{-- Modal Konfirmasi Hapus — hanya bisa ditutup lewat tombol --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Pengajuan"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
    <div class="space-y-4">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus pengajuan
        <span class="font-bold text-slate-800">{{ $deletingName ?? 'jabatan ini' }}</span>?
        Data yang sudah dihapus tidak dapat dikembalikan.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">Tutup</x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="delete">
          <span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
          <span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

  {{-- Modal Verifikasi (Super Admin) --}}
  @if($isSuperAdmin)
    <x-ui.modal show="$wire.showVerifyModal" title="Verifikasi Pengajuan Jabatan"
      description="Tetapkan cakupan keunikan lalu setujui atau tolak" icon="fa-solid fa-gavel text-primary-600" :closeable="false">
      @if($selected)
        <div class="space-y-4">
          <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs space-y-1">
            <p><span class="font-semibold text-slate-700">Jabatan diusulkan:</span> {{ $selected->name }}</p>
            <p><span class="font-semibold text-slate-700">Pengusul:</span> {{ $selected->requester?->name ?? '—' }}
              ({{ $selected->department?->name ?? 'Tanpa OPD' }})</p>
            @if($selected->reason)
              <p><span class="font-semibold text-slate-700">Alasan:</span> {{ $selected->reason }}</p>
            @endif
          </div>

          @php
            $scopeOptions = collect($scopes)
              ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
              ->all();
          @endphp
          <x-form.searchable-select wire:model="verifyScope" name="verifyScope"
            label="Cakupan Keunikan (jika disetujui)" :options="$scopeOptions"
            placeholder="— Pilih Cakupan —" searchPlaceholder="Cari cakupan..." required />

          <x-form.textarea name="verifyNote" label="Catatan / Alasan Penolakan" wire:model="verifyNote" rows="3"
            placeholder="Opsional — catatan untuk persetujuan maupun penolakan." />

          <div class="flex items-center justify-end gap-2 pt-1">
            <x-ui.button type="button" variant="danger" wire:click="reject">
              <i class="fa-solid fa-xmark"></i> Tolak
            </x-ui.button>
            <x-ui.button type="button" variant="success" wire:click="approve">
              <i class="fa-solid fa-check"></i> Setujui &amp; Buat Jabatan
            </x-ui.button>
          </div>
        </div>
      @endif
    </x-ui.modal>
  @endif

</div>
