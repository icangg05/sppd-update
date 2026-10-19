<div>
  <x-ui.modal show="$wire.show" :title="$editingId ? 'Edit Jabatan' : 'Tambah Jabatan Baru'"
    :description="$editingId ? 'Perbarui data jabatan' : 'Jabatan baru langsung bisa dipakai semua unit kerja'"
    icon="fa-solid fa-id-badge text-primary-600" maxWidth="max-w-lg" :closeable="false">
    @if ($show)
      <form wire:submit="save" class="space-y-4">
        <x-form.input id="position-form-name" label="Nama Jabatan" wire:model.live.debounce.500ms="name" required
          placeholder="Contoh: Kepala Bidang Pengawasan" />

        @if ($this->isSuperAdmin())
          <x-form.input id="position-form-level" type="number" label="Level" wire:model="level" required min="0"
            max="9999" hint="Urutan jabatan — semakin kecil semakin tinggi (mis. Walikota 1, Staf 1000)." />

          @php
            $scopeOptions = collect($scopes)
              ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
              ->all();
            $roleOptions = $roles
              ->map(fn($r) => ['value' => $r->name, 'label' => $r->label])
              ->prepend(['value' => '', 'label' => '— Tidak otomatis —'])
              ->values()
              ->all();
          @endphp
          <x-form.searchable-select wire:model.live="scope" name="scope" label="Cakupan Keunikan"
            :options="$scopeOptions" placeholder="— Pilih Cakupan —" searchPlaceholder="Cari cakupan..." required />

          <x-form.searchable-select wire:model.live="defaultRole" name="defaultRole" label="Role Sistem Default"
            :options="$roleOptions" placeholder="— Tidak otomatis —" searchPlaceholder="Cari role..."
            hint="Role yang otomatis dipilih saat pegawai diberi jabatan ini. Ditebak dari nama jabatan." />
        @else
          <fieldset class="space-y-2">
            <legend class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
              Tingkat Jabatan <span class="text-rose-500">*</span>
            </legend>
            <div class="grid gap-2 sm:grid-cols-2">
              @foreach ($tiers as $t)
                <x-form.radio wire:model.live="tier" name="tier" id="position-form-tier-{{ $t->value }}"
                  value="{{ $t->value }}" :label="$t->label()" :description="$t->description()" :error="false"
                  labelClass="h-full w-full" />
              @endforeach
            </div>
            @error('tier')
              <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror

            <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
              <i class="fa-solid fa-wand-magic-sparkles mr-1 text-primary-600"></i>
              Role pegawai: <span class="font-semibold text-slate-800">{{ $tierRoleLabel ?? 'diatur Super Admin' }}</span>
              <span class="text-slate-400">·</span>
              Cakupan: <span class="font-semibold text-slate-800">{{ $tierScope->label() }}</span>
            </div>
            <p class="text-xs text-slate-500">Tingkat ditebak otomatis dari nama jabatan — ubah bila kurang tepat.</p>
          </fieldset>
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
