<div class="p-1 space-y-4"
  x-data
  x-init="if (window.location.hash === '#ajukan-jabatan') {
    $wire.openCreateModal();
    // Hapus fragment agar modal tidak terbuka lagi saat refresh / tombol back.
    history.replaceState(null, '', window.location.pathname + window.location.search);
  }">

  {{-- Header Halaman --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid fa-id-badge text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Pengajuan Jabatan</h1>
        <p class="text-[11px] text-slate-500 font-medium">
          {{ $isSuperAdmin
            ? 'Verifikasi usulan jabatan baru dari Admin OPD dan pastikan tidak duplikat'
            : 'Ajukan jabatan baru jika belum tersedia, lalu menunggu verifikasi Super Admin' }}
        </p>
      </div>
    </div>

    <x-ui.button type="button" wire:click="openCreateModal"
      class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
      <i class="fa-solid fa-plus text-[10px]"></i>
      Ajukan Jabatan
    </x-ui.button>
  </div>

  {{-- Filter --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
    <div class="flex flex-col sm:flex-row items-center gap-2">
      <div class="relative flex-1 w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
          <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
          class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-8 text-xs focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition"
          placeholder="Cari nama jabatan...">
        <div wire:loading wire:target="search"
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-cyan-500">
          <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
        </div>
      </div>

      @php
        $statusOptions = collect($statuses)
          ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
          ->prepend(['value' => '', 'label' => 'Semua Status'])
          ->all();
      @endphp
      <div class="w-full sm:w-44">
        <x-form.searchable-select wire:model.live="statusFilter" name="statusFilter" :options="$statusOptions"
          placeholder="Semua Status" searchPlaceholder="Cari status..." class="bg-slate-50 py-1.5 text-xs" />
      </div>

      @php $canReset = $search !== '' || $statusFilter !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset"
          class="px-3 py-1.5 text-xs font-medium text-slate-600 {{ $canReset ? '' : 'opacity-50 cursor-not-allowed' }}">
          <i class="fa-solid fa-rotate-right"></i> Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Tabel --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search,statusFilter">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No</th>
            <th class="py-2.5 px-4">Nama Jabatan</th>
            <th class="py-2.5 px-4">Alasan</th>
            @if($isSuperAdmin)
              <th class="py-2.5 px-4">Pengusul</th>
              <th class="py-2.5 px-4">OPD</th>
            @endif
            <th class="py-2.5 px-4 w-28">Status</th>
            <th class="py-2.5 px-4 w-24 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse($requests as $i => $req)
            <tr wire:key="req-{{ $req->id }}" class="transition-colors hover:bg-slate-50/50">
              <td class="py-2.5 px-3 text-center text-slate-400 font-medium">
                {{ $requests->firstItem() + $i }}
              </td>
              <td class="py-2.5 px-4 font-semibold text-slate-900">{{ $req->name }}</td>
              <td class="py-2.5 px-4 text-slate-500 text-[11px] max-w-xs truncate" title="{{ $req->reason }}">
                {{ $req->reason ?? '—' }}
              </td>
              @if($isSuperAdmin)
                <td class="py-2.5 px-4 text-slate-600 font-medium">{{ $req->requester?->name ?? '—' }}</td>
                <td class="py-2.5 px-4 text-slate-500 text-[11px] truncate max-w-40">{{ $req->department?->name ?? '—' }}</td>
              @endif
              <td class="py-2.5 px-4">
                <x-ui.badge :color="$req->status->color()">{{ $req->status->label() }}</x-ui.badge>
                @if($req->status === \App\Enums\PositionRequestStatus::REJECTED && $req->review_note)
                  <p class="mt-1 text-[10px] text-rose-500 max-w-40 whitespace-normal" title="{{ $req->review_note }}">
                    {{ $req->review_note }}
                  </p>
                @endif
              </td>
              <td class="py-2.5 px-4 text-center">
                @if($isSuperAdmin && $req->status === \App\Enums\PositionRequestStatus::PENDING)
                  <button type="button" wire:click="openVerifyModal({{ $req->id }})"
                    class="inline-flex items-center gap-1 rounded border border-cyan-200 bg-cyan-50 px-2 py-1 text-[11px] font-semibold text-cyan-700 transition hover:bg-cyan-100"
                    title="Verifikasi">
                    <i class="fa-solid fa-gavel text-[10px]"></i> Verifikasi
                  </button>
                @else
                  <span class="text-slate-300">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isSuperAdmin ? '7' : '5' }}" class="py-10 text-center text-slate-400">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid fa-id-badge text-2xl opacity-40"></i>
                  <p class="font-medium">Belum ada pengajuan jabatan</p>
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

  {{-- Modal Ajukan Jabatan --}}
  <x-ui.modal show="$wire.showCreateModal" title="Ajukan Jabatan Baru"
    description="Usulkan jabatan yang belum tersedia" icon="fa-solid fa-id-badge text-cyan-600">
    <form wire:submit="submit" class="space-y-4">
      <x-form.input name="name" label="Nama Jabatan" wire:model="name" required
        placeholder="Contoh: Analis Sumber Daya Manusia Aparatur"
        hint="Cakupan keunikan (boleh diisi banyak / satu per OPD / satu sistem) ditetapkan Super Admin saat verifikasi." />

      <x-form.textarea name="reason" label="Alasan / Keterangan" wire:model="reason" rows="3"
        placeholder="Jelaskan kebutuhan jabatan ini (opsional)." />

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" x-on:click="$wire.showCreateModal = false">Batal</x-ui.button>
        <x-ui.button type="submit">
          <span wire:loading.remove wire:target="submit"><i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan</span>
          <span wire:loading wire:target="submit"><i class="fa-solid fa-spinner fa-spin"></i> Mengirim...</span>
        </x-ui.button>
      </div>
    </form>
  </x-ui.modal>

  {{-- Modal Verifikasi (Super Admin) --}}
  @if($isSuperAdmin)
    <x-ui.modal show="$wire.showVerifyModal" title="Verifikasi Pengajuan Jabatan"
      description="Tetapkan cakupan keunikan lalu setujui atau tolak" icon="fa-solid fa-gavel text-cyan-600">
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
