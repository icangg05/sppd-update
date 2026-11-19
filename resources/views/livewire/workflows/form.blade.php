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
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-diagram-project text-[9px]"></i>
          {{ $isEdit ? 'Perbarui Alur' : 'Alur Baru' }}
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">
          {{ $isEdit ? 'Edit Workflow SPPD' : 'Tambah Workflow SPPD' }}
        </h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ $isEdit
            ? 'Ubah parameter aturan urutan dan batas alur persetujuan skema SPPD terkait'
            : 'Inisiasi konfigurasi aturan urutan dan batas alur persetujuan SPPD baru' }}
        </p>
      </div>

      <x-ui.button href="{{ route('master.workflows.index') }}" variant="secondary" class="shrink-0 font-bold">
        <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
        Kembali
      </x-ui.button>
    </div>
  </div>

  <form wire:submit="save" class="space-y-4">

    {{-- Blok 1: Parameter Informasi Aturan --}}
    <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-sliders text-primary-500"></i>Konfigurasi Parameter Aturan
        </h3>
      </div>

      <div class="p-4 space-y-3">
        <div class="grid grid-cols-1 gap-x-4 gap-y-3">

          {{-- Nama Workflow --}}
          <div class="space-y-0.5">
            <x-form.input name="name" label="Nama Skema Workflow" wire:model="name"
              placeholder="Misal: Alur Staf Reguler Luar Daerah" required
              class="text-xs py-1.5 focus:border-primary-500 focus:ring-primary-500" />
          </div>

          {{-- Filter Tipe Instansi (multi-checkbox, wajib dicentang eksplisit) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Tipe Instansi <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border rounded text-xs @error('department_type') border-rose-300 @else border-slate-200 @enderror">
              @foreach ($departmentTypes as $type)
                <x-form.checkbox wire:model.live="department_type" :value="$type->value" :label="$type->label()" :show-error="false"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            @error('department_type')
              <p class="text-[11px] font-semibold text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Centang setiap tipe instansi yang memakai alur ini. Tipe yang tidak dicentang tidak memakai alur ini.</p>
          </div>

          {{-- Filter Peran Pemohon (multi-checkbox, wajib dicentang eksplisit) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Peran Pemohon <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border rounded text-xs @error('applicant_role') border-rose-300 @else border-slate-200 @enderror">
              @foreach ($roles as $role)
                <x-form.checkbox wire:model.live="applicant_role" :value="$role->name" :label="$roleLabelOf($role)" :show-error="false"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            @error('applicant_role')
              <p class="text-[11px] font-semibold text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Centang setiap peran pemohon yang memakai alur ini. Peran yang tidak dicentang tidak memakai alur ini.</p>
          </div>

          {{-- Pilihan Ruang Lingkup Wilayah Tujuan (wajib dicentang eksplisit) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Cakupan Wilayah Tujuan <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-2.5 bg-slate-50 border rounded text-xs @error('destination') border-rose-300 @else border-slate-200 @enderror">
              @foreach ($domains as $domain)
                <x-form.checkbox wire:model.live="destination" :value="$domain->value" :label="$domain->label()" :show-error="false"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            @error('destination')
              <p class="text-[11px] font-semibold text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Centang setiap wilayah tujuan yang memakai alur ini. Wilayah yang tidak dicentang tidak memakai alur ini.</p>
          </div>

          {{-- Cakupan beririsan dengan alur aktif lain: hanya alur yang dibuat lebih dulu yang terpakai,
               jadi penyimpanan ditolak sampai irisannya dihapus (lihat WorkflowForm::overlaps). --}}
          @if ($conflicts)
            <x-ui.alert title="Cakupan ini bentrok dengan alur aktif lain">
              <p>Pada irisan di bawah, sistem hanya memakai alur yang dibuat lebih dulu, jadi salah satunya tidak akan pernah terpakai. Hapus centang yang beririsan, ubah alur lawannya, atau simpan alur ini dalam keadaan nonaktif dulu.</p>
              <ul class="mt-2 space-y-1.5">
                @foreach ($conflicts as $conflict)
                  <li wire:key="conflict-{{ $conflict['id'] }}">
                    <a href="{{ route('master.workflows.edit', $conflict['id']) }}" target="_blank" rel="noopener"
                      class="font-semibold underline underline-offset-2 hover:no-underline">{{ $conflict['name'] }} <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i></a>
                    <span class="block text-xs">Tipe: {{ $conflict['department_type'] }} · Peran: {{ $conflict['applicant_role'] }} · Wilayah: {{ $conflict['destination'] }}</span>
                  </li>
                @endforeach
              </ul>
            </x-ui.alert>
          @endif

          {{-- Status Keaktifan Aturan --}}
          <div class="pt-1">
            <x-form.checkbox wire:model.live="is_active" label="Aktifkan Aturan Perubahan Workflow Langsung"
              wrapper-class="flex items-center gap-2 font-bold text-xs text-slate-800 cursor-pointer" />
          </div>
        </div>
      </div>
    </div>

    {{-- Blok 2: Manajemen Alur Urutan Persetujuan --}}
    <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
        <div class="space-y-0.5">
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
            <i class="fa-solid fa-diagram-next text-primary-500"></i>Alur Urutan Persetujuan (Steps) <span class="text-rose-500">*</span>
          </h3>
          <p class="text-[10px] text-slate-500 font-medium">Tentukan skema tingkatan peran aparatur penandatangan dari urutan awal hingga akhir. Tingkat persetujuan terendah adalah Kabid / Irban / Kabag.</p>
          <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-grip-vertical text-primary-500 mr-1"></i>Seret pegangan di kiri tiap tahap untuk mengubah urutan, atau fokuskan lalu tekan panah atas/bawah.</p>
          <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Setiap tahap wajib dijalankan. Pemohon yang tahapnya belum punya pejabat tidak bisa membuat SPPD sampai pejabatnya dilengkapi.</p>
        </div>

        <x-ui.button type="button" wire:click="addStep" variant="secondary"
          class="px-2.5 py-1 text-[11px] font-bold text-slate-700">
          <i class="fa-solid fa-plus text-primary-600 text-[10px]"></i> Sisipkan Tahap
        </x-ui.button>
      </div>

      <div class="p-4">
        @error('steps')
          <div class="p-2.5 mb-3 bg-rose-50 border border-rose-200 rounded text-rose-700 text-[11px] font-medium flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}
          </div>
        @enderror

        @php
          // Ditambah tahap eksternal: pejabat dicari di instansi lain (mis. Kepala Dinas Kesehatan).
          $roleOptions = collect($approverRoles)
            ->map(fn ($r) => ['value' => $r->name, 'label' => $roleLabelOf($r)])
            ->concat(collect($externalStepRoles)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values())
            ->all();
        @endphp
        {{-- Seret-lepas lewat wire:sort (SortableJS bawaan Livewire), hanya dari pegangan.
             Nomor tahap memakai CSS counter agar langsung ikut berubah selama diseret; salinan
             yang melayang (mode sentuh) ikut terhitung di akhir daftar, jadi nomornya disembunyikan.
             Keyboard: panah atas/bawah pada pegangan memindah tahap, fokus ikut ke posisi barunya. --}}
        <ol wire:sort.ghost="sortStep" x-data="{
            move(i, dir) {
              $wire.moveStep(i, dir).then(() => this.$nextTick(() =>
                this.$refs.steps.querySelectorAll('[data-step-handle]')[i + dir]?.focus()))
            }
          }" x-ref="steps" class="flex max-w-2xl flex-col gap-2 [counter-reset:step]">
          @foreach ($steps as $i => $step)
            {{-- Kunci urut mulai 1: wire:sort menganggap kunci 0 sebagai kosong. --}}
            <li wire:key="step-{{ $i }}" wire:sort:item="{{ $i + 1 }}"
              class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded shadow-sm [counter-increment:step] [&.sortable-ghost]:border-dashed [&.sortable-ghost]:border-primary-400 [&.sortable-ghost]:bg-primary-50/60 [&.sortable-ghost]:shadow-none [&.sortable-ghost>*]:invisible [&.sortable-fallback_.step-number]:text-transparent">
              <div class="flex items-center gap-2 flex-1 w-full">
                {{-- Span (bukan <button>): Firefox tidak memulai seret native dari tombol. --}}
                <span role="button" tabindex="0" wire:sort:handle data-step-handle
                  x-on:keydown.arrow-up.prevent="move({{ $i }}, -1)" x-on:keydown.arrow-down.prevent="move({{ $i }}, 1)"
                  class="flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 active:cursor-grabbing focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40"
                  title="Seret untuk mengubah urutan" aria-label="Pindahkan tahap {{ $i + 1 }}: seret, atau tekan panah atas/bawah">
                  <i class="fa-solid fa-grip-vertical text-xs"></i>
                </span>
                <div aria-hidden="true"
                  class="step-number w-6 h-6 flex items-center justify-center bg-slate-200 border border-slate-300 text-slate-700 font-black rounded text-[11px] shrink-0 shadow-inner before:content-[counter(step)]">
                </div>

                <div class="flex-1">
                  {{-- wire:key memuat ulang pilihan saat sebutan role berubah (tipe instansi diganti). --}}
                  <x-form.searchable-select wire:model="steps.{{ $i }}.role" :options="$roleOptions" wire:key="step-role-{{ $i }}-{{ md5(json_encode($roleOptions)) }}"
                    placeholder="— Pilih Pejabat Penyetuju —" searchPlaceholder="Cari role..."
                    class="px-2.5 py-1.5 text-xs" />
                </div>
              </div>

              <div class="flex items-center justify-between sm:justify-start gap-4 px-2 sm:px-0 w-full sm:w-auto shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
                <div class="flex items-center gap-4">
                  <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-primary-600 transition-colors">
                    <input type="checkbox" wire:model.live="steps.{{ $i }}.signs_spt"
                      class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 transition-colors">
                    <span>TTD SPT</span>
                  </label>

                  <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-primary-600 transition-colors">
                    <input type="checkbox" wire:model.live="steps.{{ $i }}.signs_sppd"
                      class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 transition-colors">
                    <span>TTD SPPD</span>
                  </label>
                </div>

                <button type="button" wire:click="removeStep({{ $i }})"
                  class="p-1.5 text-slate-500 border border-transparent rounded hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 transition-colors shrink-0"
                  title="Hapus Tahap">
                  <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
              </div>
            </li>
          @endforeach
        </ol>
      </div>
    </div>

    {{-- Form Actions Footer --}}
    <div class="pt-1 flex items-center justify-end gap-2">
      <a wire:navigate href="{{ route('master.workflows.index') }}"
        class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
        Batal
      </a>

      <x-ui.button type="submit"
        class="px-4 py-1.5 text-xs font-bold shadow-sm shadow-primary-200">
        <span wire:loading.remove wire:target="save">
          <i class="fa-solid fa-floppy-disk text-[11px]"></i>
          {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Aturan Workflow' }}
        </span>
        <span wire:loading wire:target="save"><i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...</span>
      </x-ui.button>
    </div>
  </form>
</div>
