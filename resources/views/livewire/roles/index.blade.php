<div class="p-1 space-y-4">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-violet-100 rounded text-violet-600">
        <i class="fa-solid fa-shield-halved text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Kelola Role</h1>
        <p class="text-[11px] text-slate-500 font-medium">Atur hak akses peran pengguna dalam sistem</p>
      </div>
    </div>

    <a wire:navigate href="{{ route('master.roles.create') }}"
      class="inline-flex items-center gap-1.5 rounded bg-violet-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-violet-200 transition hover:bg-violet-700 hover:shadow-lg">
      <i class="fa-solid fa-plus text-[10px]"></i>
      Tambah Role
    </a>
  </div>

  {{-- Filter / Pencarian --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
    <div class="flex flex-col sm:flex-row items-center gap-2">
      <div class="relative flex-1 w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
          <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
          class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-8 text-xs focus:border-violet-500 focus:bg-white focus:ring-1 focus:ring-violet-500 outline-none transition"
          placeholder="Cari nama atau label role...">
        <div wire:loading wire:target="search"
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-violet-500">
          <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
        </div>
      </div>

      @php $canReset = $search !== ''; @endphp
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
              <td class="py-2.5 px-3 text-center text-slate-400 font-medium">{{ $roles->firstItem() + $i }}.</td>

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
                  <span class="text-slate-400 italic text-[11px]">Tidak ada permission</span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                @if ($role->users_count > 0)
                  <a href="{{ route('master.users.index', ['role' => $role->name]) }}" wire:navigate
                    class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700 ring-1 ring-violet-600/20 transition hover:bg-violet-100"
                    title="Lihat pegawai dengan role ini">
                    <i class="fa-solid fa-users text-[10px]"></i> {{ $role->users_count }}
                  </a>
                @else
                  <span class="text-slate-400 font-medium">0</span>
                @endif
              </td>

              <td class="py-2.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <a wire:navigate href="{{ route('master.roles.edit', $role->id) }}"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </a>

                  @if ($role->name !== 'super_admin')
                    <button type="button" wire:click="confirmDelete({{ $role->id }})"
                      class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                      title="Hapus">
                      <i class="fa-solid fa-trash-can text-[10px]"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-10 text-center text-slate-400">
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
