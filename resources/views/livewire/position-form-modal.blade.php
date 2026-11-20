<div>
  <x-ui.modal show="$wire.show" :title="$editingId ? 'Edit Jabatan' : 'Tambah Jabatan Baru'"
    :description="$editingId ? 'Perbarui data jabatan' : 'Jabatan baru langsung bisa dipakai semua unit kerja'"
    icon="fa-solid fa-id-badge text-primary-600" maxWidth="max-w-lg" :closeable="false">
    @if ($show)
      <form wire:submit="save" class="space-y-4">
        <x-form.input id="position-form-name" label="Nama Jabatan" wire:model.live.debounce.500ms="name" required
          :placeholder="$isTenagaMitra ? 'Contoh: Dosen Universitas Halu Oleo' : 'Contoh: Kepala Bidang Pengawasan'" />

        {{-- Jabatan Tenaga Mitra cukup nama: tanpa tingkat jabatan (role selalu Tenaga Mitra) dan level. --}}
        @unless ($isTenagaMitra)
        <fieldset class="space-y-2">
          <legend class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
            Tingkat Jabatan <span class="text-rose-500">*</span>
          </legend>
          @if ($fixedTier)
            {{-- Dari wizard pejabat alur: tingkat mengikuti tahap yang sedang diisi. --}}
            <div class="flex items-start gap-2.5 rounded border border-primary-200 bg-primary-50 px-3 py-2">
              <i class="fa-solid fa-lock mt-0.5 text-xs text-primary-700"></i>
              <div class="min-w-0 text-sm leading-tight">
                <p class="font-semibold text-primary-900">{{ $fixedTier['label'] }}</p>
                @if ($fixedTier['description'])
                  <p class="mt-0.5 text-xs text-primary-800">{{ $fixedTier['description'] }}</p>
                @endif
                <p class="mt-1 text-xs text-primary-800">Mengikuti tahap alur persetujuan yang sedang dilengkapi.</p>
              </div>
            </div>
          @else
            @if ($tier === \App\Enums\PositionTier::LAINNYA->value)
              {{-- Jabatan khusus lama (Walikota, Sekda, dst.) di luar pilihan tingkat: role dipertahankan
                   selama tingkat tidak diganti. --}}
              <div class="flex items-start gap-2.5 rounded border border-amber-200 bg-amber-50 px-3 py-2">
                <i class="fa-solid fa-circle-info mt-0.5 text-xs text-amber-600"></i>
                <p class="text-xs leading-relaxed text-amber-800">
                  Jabatan ini memakai role <span class="font-semibold">{{ $summaryRole ?? 'yang belum ditentukan' }}</span>
                  di luar pilihan tingkat di bawah. Biarkan agar role tetap, atau pilih salah satu tingkat untuk menggantinya.
                </p>
              </div>
            @endif
            <div class="grid gap-2 sm:grid-cols-2">
              @foreach ($tiers as $t)
                <x-form.radio wire:model.live="tier" name="tier" id="position-form-tier-{{ $t->value }}"
                  value="{{ $t->value }}" :label="$t->label()" :description="$t->description()" :error="false"
                  labelClass="h-full w-full" />
              @endforeach
            </div>
          @endif
          @error('tier')
            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
          @enderror

          @if ($unitRoleOptions)
            {{-- Pimpinan/Sekretaris: role umum yang menyesuaikan unit kerja, atau role khusus satu tipe unit. --}}
            <div class="space-y-1.5 pt-1">
              <p class="text-xs font-bold tracking-wide text-slate-600 uppercase">Role</p>
              <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($unitRoleOptions as $option)
                  <x-form.radio wire:model.live="unitRole" name="unitRole" id="position-form-unit-role-{{ $option['value'] ?: 'umum' }}"
                    value="{{ $option['value'] }}" :label="$option['label']" :description="$option['description']" :error="false"
                    labelClass="h-full w-full" />
                @endforeach
              </div>
            </div>
          @endif

          <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
            <i class="fa-solid fa-wand-magic-sparkles mr-1 text-primary-600"></i>
            Role pegawai: <span class="font-semibold text-slate-800">{{ $summaryRole ?? 'belum ditentukan' }}</span>
            <span class="text-slate-400">·</span>
            Cakupan: <span class="font-semibold text-slate-800">{{ $summaryScope->label() }}</span>
            @if ($roleVariants && ! $unitRoleOptions)
              <span class="mt-0.5 block text-slate-500">Menyesuaikan unit kerja: {{ $roleVariants }}.</span>
            @endif
          </div>
          @unless ($fixedTier)
            <p class="text-xs text-slate-500">Tingkat ditebak otomatis dari nama jabatan, ubah bila kurang tepat. Role
              akhir pegawai menyesuaikan unit kerja dan alur SPPD-nya, misalnya Pimpinan di Puskesmas menjadi Kepala
              Puskesmas dan Kabid di Dinas tetap Staf.</p>
          @endunless
        </fieldset>
        @endunless

        @if ($this->isSuperAdmin() && ! $isTenagaMitra)
          <x-form.input id="position-form-level" type="number" label="Level" wire:model.live.debounce.500ms="level"
            required min="0" max="9999"
            hint="Terisi otomatis dari tingkat jabatan dan bisa diubah. Angka lebih kecil berarti jabatan lebih tinggi, misalnya Walikota 1, Kepala OPD 20, dan Staf 1000. Dipakai untuk mengurutkan nama pengikut di dokumen SPT." />
        @endif

        <div class="flex items-center justify-end gap-2 pt-1">
          <x-ui.button type="button" variant="secondary" x-on:click="$wire.show = false">Batal</x-ui.button>
          <x-ui.button type="submit" wire:target="save">
            <i class="fa-solid fa-floppy-disk"></i> {{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}
          </x-ui.button>
        </div>
      </form>
    @endif
  </x-ui.modal>
</div>
