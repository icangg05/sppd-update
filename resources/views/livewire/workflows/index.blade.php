<div class="p-1 space-y-4">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-route pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-diagram-project text-[9px]"></i>
          Alur Persetujuan
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $workflows->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Workflow SPPD</h1>
        <p class="mt-1 text-xs text-slate-500">Atur alur dan tahapan persetujuan dokumen SPPD secara dinamis</p>
      </div>

      <x-ui.button href="{{ route('master.workflows.create') }}" variant="primary" class="shrink-0 font-bold">
        <i class="fa-solid fa-plus text-xs"></i> Tambah Workflow
      </x-ui.button>
    </div>
  </div>

  {{-- Filter / Pencarian --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-4">
    <div class="flex flex-col gap-3 sm:flex-row">
      <x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" wrapperClass="flex-1 w-full"
        placeholder="Cari nama workflow..." />

      @php $canReset = $search !== ''; @endphp
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
    wire:loading.class="opacity-60" wire:target="search">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No.</th>
            <th class="py-2.5 px-4">Nama Workflow</th>
            <th class="py-2.5 px-4">Instansi & Jabatan</th>
            <th class="py-2.5 px-4">Tujuan Wilayah</th>
            <th class="py-2.5 px-4">Alur Tahapan Persetujuan (Steps)</th>
            <th class="py-2.5 px-4 w-20 text-center">Status</th>
            <th class="py-2.5 px-4 w-24 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse($workflows as $i => $w)
            <tr wire:key="workflow-{{ $w->id }}" class="hover:bg-slate-50/50 transition-colors">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">
                {{ $workflows->firstItem() + $i }}.
              </td>

              <td class="py-2.5 px-4">
                <span class="font-bold text-slate-900 tracking-wide whitespace-pre-wrap">{{ $w->name }}</span>
              </td>

              <td class="py-2.5 px-4">
                <div class="space-y-1 text-[11px]">
                  <span class="block text-slate-700 font-medium">
                    <i class="fa-solid fa-building text-slate-500 mr-1 text-[10px]"></i>Tipe Instansi:
                    @if (is_array($w->department_type) && count($w->department_type) > 0)
                      <div class="flex flex-wrap gap-1 mt-0.5 ml-4">
                        @foreach ($w->department_type as $dt)
                          <span
                            class="inline-flex items-center rounded bg-slate-100 px-1 py-0.5 text-[9px] font-bold text-slate-600 border border-slate-200">
                            {{ \App\Enums\DepartmentType::tryFrom($dt)?->label() ?? $dt }}
                          </span>
                        @endforeach
                      </div>
                    @else
                      Semua
                    @endif
                  </span>
                  <span class="block text-slate-500">
                    <i class="fa-solid fa-user-tag text-slate-500 mr-1 text-[10px]"></i>Pemohon:
                    @if (is_array($w->applicant_role) && count($w->applicant_role) > 0)
                      <div class="flex flex-wrap gap-1 mt-0.5 ml-4">
                        @foreach ($w->applicant_role as $role)
                          <span
                            class="inline-flex items-center rounded bg-primary-50 px-1 py-0.5 text-[9px] font-bold text-primary-700 border border-primary-100/70">
                            {{ $roleLabels[$role] ?? $role }}
                          </span>
                        @endforeach
                      </div>
                    @else
                      Semua
                    @endif
                  </span>
                </div>
              </td>

              <td class="py-2.5 px-4">
                @if (is_array($w->destination) && count($w->destination) > 0)
                  <div class="flex flex-wrap gap-1">
                    @foreach ($w->destination as $d)
                      <span
                        class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-100/70 uppercase">
                        {{ \App\Enums\SppdDomain::tryFrom($d)?->label() ?? $d }}
                      </span>
                    @endforeach
                  </div>
                @else
                  <span
                    class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-200">
                    Semua
                  </span>
                @endif
              </td>

              <td class="py-2.5 px-4">
                <div class="flex flex-wrap gap-1.5 items-center">
                  @foreach ($w->steps as $idx => $step)
                    @php
                      $roleName = is_array($step) ? $step['role'] ?? '' : $step;
                      $signsSpt = is_array($step) ? (bool) ($step['signs_spt'] ?? false) : false;
                      $signsSppd = is_array($step) ? (bool) ($step['signs_sppd'] ?? false) : false;
                      $roleLabel = $roleLabels[$roleName] ?? ucwords(str_replace('_', ' ', $roleName));
                    @endphp
                    <div
                      class="inline-flex items-center gap-1.5 rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] text-slate-700 font-medium shadow-sm">
                      <span class="text-primary-600 font-black">{{ $idx + 1 }}.</span>
                      <span>{{ $roleLabel }}</span>
                      @if ($signsSpt || $signsSppd)
                        <div class="flex items-center gap-0.5 ml-1 select-none">
                          @if ($signsSpt)
                            <span
                              class="inline-flex items-center rounded bg-amber-100 px-1 py-0.25 text-[8px] font-black text-amber-700 uppercase tracking-tight scale-90"
                              title="Menandatangani Dokumen SPT">SPT</span>
                          @endif
                          @if ($signsSppd)
                            <span
                              class="inline-flex items-center rounded bg-teal-100 px-1 py-0.25 text-[8px] font-black text-teal-700 uppercase tracking-tight scale-90"
                              title="Menandatangani Dokumen SPPD">SPPD</span>
                          @endif
                        </div>
                      @endif
                    </div>
                    @if (!$loop->last)
                      <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 mx-0.5 shrink-0"></i>
                    @endif
                  @endforeach
                </div>
              </td>

              <td class="py-2.5 px-4 text-center">
                @if ($w->is_active)
                  <span
                    class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200 uppercase">
                    Aktif
                  </span>
                @else
                  <span
                    class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200 uppercase">
                    Nonaktif
                  </span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <a wire:navigate href="{{ route('master.workflows.edit', $w->id) }}"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-500 hover:bg-amber-50 hover:text-amber-600 transition-colors"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </a>

                  <button type="button" wire:click="confirmDelete({{ $w->id }})"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                    title="Hapus">
                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-10 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid fa-diagram-project text-2xl opacity-40"></i>
                  <p class="font-medium">Belum ada pengaturan alur urutan workflow yang tersimpan</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($workflows->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $workflows->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Konfirmasi Hapus — tombol Hapus aktif setelah 10 detik --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Workflow"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
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
      x-on:workflow-delete-countdown.window="startCountdown()"
      x-init="if ($wire.showDeleteModal) startCountdown()">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus workflow
        <span class="font-bold text-slate-800">{{ $deletingName ?? 'ini' }}</span>?
        Data yang sudah dihapus tidak dapat dikembalikan.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">Tutup</x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="delete"
          x-bind:disabled="remaining > 0"
          x-bind:class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''">
          <span x-show="remaining > 0"><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="remaining"></span>s</span>
          <span x-show="remaining <= 0" x-cloak>
            <span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
            <span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
          </span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

</div>
