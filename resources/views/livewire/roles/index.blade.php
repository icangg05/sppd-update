<div class="p-1 space-y-4">

  {{-- Header (title card — aksen violet identitas Role) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-violet-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-shield-halved pointer-events-none absolute -right-3 -top-4 text-8xl text-violet-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-violet-700 ring-1 ring-inset ring-violet-600/15">
          <i class="fa-solid fa-user-shield text-[9px]"></i>
          Hak Akses
          <span class="ml-1 tabular-nums text-violet-600/70">· {{ $roles->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Kelola Role</h1>
        <p class="mt-1 text-xs text-slate-500">Atur hak akses peran pengguna dalam sistem</p>
      </div>

      <x-ui.button href="{{ route('master.roles.create') }}" variant="violet" class="shrink-0 font-bold">
        <x-slot name="icon"><i class="fa-solid fa-plus text-[10px]"></i></x-slot>
        Tambah Role
      </x-ui.button>
    </div>
  </div>

  {{-- Filter / Pencarian --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-4">
    <div class="flex flex-col gap-3 sm:flex-row">
      <x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" wrapperClass="flex-1 w-full"
        placeholder="Cari nama atau label role..." />

      @php $canReset = $search !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset">
          <x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
          Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-10 text-center">No.</th>
            <th class="py-2.5 px-4">Nama Role</th>
            <th class="py-2.5 px-4">Label</th>
            <th class="py-2.5 px-4">Permissions</th>
            <th class="py-2.5 px-4 w-28 text-center">Pengguna</th>
            <th class="py-2.5 px-4 w-24 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse ($roles as $i => $role)
            <tr wire:key="role-{{ $role->id }}" class="hover:bg-slate-50/50 transition-colors">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">{{ $roles->firstItem() + $i }}.</td>

              <td class="py-2.5 px-4">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-mono text-slate-700">{{ $role->name }}</code>
                @if ($role->name === 'super_admin')
                  <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1 py-0.5 text-[9px] font-black text-amber-700 uppercase">Protected</span>
                @endif
              </td>

              <td class="py-2.5 px-4 font-semibold text-slate-800">
                {{ $role->label ?? '-' }}
              </td>

              <td class="py-2.5 px-4">
                @if ($role->permissions->count() > 0)
                  <div class="flex flex-wrap gap-1">
                    @foreach ($role->permissions->take(5) as $perm)
                      <span class="inline-flex items-center rounded bg-violet-50 px-1.5 py-0.5 text-[9px] font-bold text-violet-700 border border-violet-100">
                        {{ $perm->name }}
                      </span>
                    @endforeach
                    @if ($role->permissions->count() > 5)
                      <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 border border-slate-200">
                        +{{ $role->permissions->count() - 5 }} lainnya
                      </span>
                    @endif
                  </div>
                @else
                  <span class="text-slate-500 italic text-[11px]">Tidak ada permission</span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                @if ($role->users_count > 0)
                  <a href="{{ route('master.users.index', array_filter(['role' => $role->name, 'type' => in_array($role->name, ['anggota_dprd', 'pimpinan_dprd']) ? 'dprd' : ''])) }}" wire:navigate
                    class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700 ring-1 ring-violet-600/20 transition hover:bg-violet-100"
                    title="Lihat pegawai dengan role ini">
                    <i class="fa-solid fa-users text-[10px]"></i> {{ $role->users_count }}
                  </a>
                @else
                  <span class="text-slate-500 font-medium">0</span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <a wire:navigate href="{{ route('master.roles.edit', $role->id) }}"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-500 hover:bg-amber-50 hover:text-amber-600 transition-colors"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </a>

                  @if ($role->name !== 'super_admin')
                    <button type="button" wire:click="confirmDelete({{ $role->id }})"
                      class="rounded border border-slate-200 bg-white p-1 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                      title="Hapus">
                      <i class="fa-solid fa-trash-can text-[10px]"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-10 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid fa-shield-halved text-2xl opacity-40"></i>
                  <p class="font-medium">Belum ada role yang tersimpan</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($roles->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $roles->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Konfirmasi Hapus — tombol Hapus aktif setelah 10 detik --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Role"
    description="Semua pengguna dengan role ini akan kehilangan aksesnya" icon="fa-solid fa-trash-can text-rose-600"
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
      x-on:role-delete-countdown.window="startCountdown()"
      x-init="if ($wire.showDeleteModal) startCountdown()">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus role
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
