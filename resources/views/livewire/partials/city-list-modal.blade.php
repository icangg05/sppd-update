{{--
  Modal daftar kota milik satu wilayah induk (Provinsi → Kabupaten/Kota, Negara → Kota).
  Variabel: $title, $description, $names (list nama urut abjad, kosong saat modal tertutup),
  $ownerId, $manageUrl, $manageLabel. Pencarian di dalam modal berjalan di browser
  (tanpa request) karena satu negara bisa punya ratusan kota.
--}}
<x-ui.modal show="$wire.showCitiesModal" :title="$title" :description="$description"
  icon="fa-solid fa-city text-primary-600" maxWidth="max-w-lg">
  @if ($ownerId)
    {{-- wire:key per wilayah: ganti wilayah = komponen Alpine baru (kata kunci & daftar ikut baru). --}}
    <div wire:key="city-list-{{ $ownerId }}" class="space-y-3"
      x-data="{
        q: '',
        all: @js(collect($names)->values()->map(fn ($name, $i) => ['no' => $i + 1, 'name' => $name])),
        get list() {
          const term = this.q.trim().toLowerCase();
          return term ? this.all.filter((city) => city.name.toLowerCase().includes(term)) : this.all;
        },
      }">
      @if (count($names) > 8)
        <x-form.input id="city-list-search" x-model="q" icon="fa-solid fa-magnifying-glass"
          placeholder="Cari nama kota..." autocomplete="off" data-autofocus />
      @endif

      @if (count($names) > 0)
        <ol x-show="list.length > 0"
          class="grid max-h-[min(60vh,26rem)] overflow-y-auto rounded border border-slate-200 text-xs text-slate-700 sm:grid-cols-2">
          <template x-for="city in list" :key="city.no">
            <li class="flex items-baseline gap-2 border-b border-slate-100 px-3 py-1.5">
              <span class="w-8 shrink-0 text-right tabular-nums text-slate-400" x-text="city.no + '.'"></span>
              <span class="min-w-0 font-medium text-slate-800" x-text="city.name"></span>
            </li>
          </template>
        </ol>
        <p x-show="list.length === 0" x-cloak class="py-6 text-center text-xs text-slate-500">
          Tidak ada kota yang cocok dengan "<span class="font-semibold" x-text="q.trim()"></span>".
        </p>
      @else
        <p class="py-6 text-center text-xs text-slate-500">Belum ada kota yang terdaftar.</p>
      @endif
    </div>
  @endif

  <x-slot:footer class="flex flex-wrap items-center justify-end gap-2">
    <x-ui.button variant="secondary" x-on:click="$wire.showCitiesModal = false">Tutup</x-ui.button>
    @if ($ownerId)
      {{-- Tutup dulu agar penghitung kunci scroll modal kembali nol sebelum pindah halaman (wire:navigate). --}}
      <x-ui.button :href="$manageUrl" x-on:click="$wire.showCitiesModal = false">
        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> {{ $manageLabel }}
      </x-ui.button>
    @endif
  </x-slot:footer>
</x-ui.modal>
