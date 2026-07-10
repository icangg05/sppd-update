<div class="mx-auto max-w-4xl space-y-6"
  x-data="{
    showConfirm: false,
    countdown: 10,
    timer: null,
    openConfirm() {
      this.countdown = 10;
      this.showConfirm = true;
      clearInterval(this.timer);
      this.timer = setInterval(() => {
        if (this.countdown > 0) this.countdown--;
        if (this.countdown <= 0) clearInterval(this.timer);
      }, 1000);
    },
    closeConfirm() {
      this.showConfirm = false;
      clearInterval(this.timer);
    },
    showDelete: false,
    deleteTarget: '',
    deleteCountdown: 10,
    deleteTimer: null,
    openDelete(name) {
      this.deleteTarget = name;
      this.deleteCountdown = 10;
      this.showDelete = true;
      clearInterval(this.deleteTimer);
      this.deleteTimer = setInterval(() => {
        if (this.deleteCountdown > 0) this.deleteCountdown--;
        if (this.deleteCountdown <= 0) clearInterval(this.deleteTimer);
      }, 1000);
    },
    closeDelete() {
      this.showDelete = false;
      clearInterval(this.deleteTimer);
    },
  }"
  x-on:open-backup-confirm.window="openConfirm()">

  {{-- Polling status backup yang sedang diproses worker --}}
  @if ($processing)
    <div wire:poll.2s="pollBackup"></div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h1 class="page-title">Backup Database</h1>
      <p class="page-subtitle">Buat dan unduh cadangan database sistem.</p>
    </div>
    @if ($processing)
      <x-ui.button type="button" disabled>
        <i class="fa-solid fa-spinner fa-spin text-xs"></i> Memproses di worker...
      </x-ui.button>
    @else
      {{-- Cek worker dulu di server (requestBackup); modal baru dibuka bila worker sehat. --}}
      <x-ui.button type="button" wire:click="requestBackup" wire:loading.attr="disabled" wire:target="requestBackup">
        <span wire:loading.remove wire:target="requestBackup"><i class="fa-solid fa-database text-xs"></i> Buat Backup Sekarang</span>
        <span wire:loading wire:target="requestBackup"><i class="fa-solid fa-spinner fa-spin text-xs"></i> Memeriksa worker...</span>
      </x-ui.button>
    @endif
  </div>

  {{-- Info database --}}
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="table-container flex items-center gap-3 p-4">
      <div class="flex size-10 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-database"></i></div>
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Database</p>
        <p class="truncate text-sm font-bold text-slate-800" title="{{ $database }}">{{ $database }}</p>
      </div>
    </div>
    <div class="table-container flex items-center gap-3 p-4">
      <div class="flex size-10 items-center justify-center rounded bg-violet-50 text-violet-600"><i class="fa-solid fa-table-list"></i></div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Tabel</p>
        <p class="text-sm font-bold text-slate-800">{{ $tableCount }} tabel</p>
      </div>
    </div>
    <div class="table-container flex items-center gap-3 p-4">
      <div class="flex size-10 items-center justify-center rounded bg-amber-50 text-amber-600"><i class="fa-solid fa-weight-hanging"></i></div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ukuran Data</p>
        <p class="text-sm font-bold text-slate-800">{{ $sizeMb }} MB</p>
      </div>
    </div>
  </div>

  {{-- Catatan --}}
  <div class="flex items-start gap-3 rounded border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
    <i class="fa-solid fa-robot mt-0.5 shrink-0"></i>
    @php
      $backupDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      $backupDay = $backupDays[(int) config('backup.day', 0)] ?? 'Minggu';
      $backupTime = config('backup.time', '03:00');
      $backupKeep = (int) config('backup.keep', 8);
    @endphp
    <p>
      <strong>Backup otomatis</strong> berjalan tiap <strong>{{ $backupDay }} pukul {{ $backupTime }}</strong> di latar belakang (worker).
      Sistem menyimpan <strong>1 backup per minggu</strong> dan hanya menjaga <strong>{{ $backupKeep }} terbaru</strong> (± {{ $backupKeep }} minggu terakhir).
      Menekan tombol di minggu yang sama akan <strong>menimpa</strong> backup minggu itu.
    </p>
  </div>

  {{-- Catatan restore --}}
  <div class="flex items-start gap-3 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
    <p>
      File berformat <strong>.sql.gz</strong> (SQL terkompresi). Simpan di tempat aman.
      <strong>Restore</strong> sengaja tidak disediakan lewat aplikasi demi keamanan — pemulihan
      sebaiknya dilakukan administrator melalui CLI pada jendela pemeliharaan terkontrol.
    </p>
  </div>

  {{-- Daftar backup --}}
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>No.</th>
          <th>Nama File</th>
          <th>Ukuran</th>
          <th>Dibuat</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($backups as $i => $backup)
          <tr wire:key="bk-{{ $backup['name'] }}">
            <td>{{ $i + 1 }}.</td>
            <td class="font-mono text-xs text-slate-700">{{ $backup['name'] }}</td>
            <td>{{ number_format($backup['size'] / 1024, 1) }} KB</td>
            <td>{{ $backup['created_at']->translatedFormat('d M Y, H:i') }}</td>
            <td>
              <div class="flex items-center justify-end gap-2">
                {{-- Disable + spinner selama server menyiapkan file (cegah spam klik).
                     wire:target memuat argumen agar hanya tombol baris ini yang terkunci. --}}
                <button wire:click="download('{{ $backup['name'] }}')"
                  wire:loading.attr="disabled" wire:target="download('{{ $backup['name'] }}')"
                  class="inline-flex items-center gap-1.5 rounded border border-primary-500 bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-60">
                  <span wire:loading.remove wire:target="download('{{ $backup['name'] }}')">
                    <i class="fa-solid fa-download"></i> Unduh
                  </span>
                  <span wire:loading wire:target="download('{{ $backup['name'] }}')">
                    <i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan...
                  </span>
                </button>
                <button type="button" x-on:click="openDelete('{{ $backup['name'] }}')"
                  class="inline-flex items-center gap-1.5 rounded border border-red-400 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-600 hover:text-white">
                  <i class="fa-solid fa-trash-can"></i> Hapus
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
              <i class="fa-solid fa-box-open mb-2 block text-2xl"></i>
              Belum ada backup. Klik <strong>Buat Backup Sekarang</strong> untuk membuat yang pertama.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Modal konfirmasi backup dengan jeda 10 detik --}}
  <x-ui.modal show="showConfirm" :closeable="false" title="Konfirmasi Backup Database"
    description="Proses akan dikirim ke worker" icon="fa-solid fa-database text-primary-600" maxWidth="max-w-md">
    <div class="space-y-3 text-sm text-slate-600">
      <p>Anda akan membuat backup database <strong>minggu ini</strong>. Jika backup minggu ini sudah ada,
        file tersebut akan <strong>ditimpa</strong>.</p>
      <div class="flex items-start gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
        <i class="fa-regular fa-clock mt-0.5"></i>
        <span>Demi keamanan, tombol konfirmasi aktif setelah <strong>10 detik</strong>.</span>
      </div>
    </div>

    <x-slot name="footer" class="flex items-center justify-end gap-3 bg-slate-50">
      <button type="button" x-on:click="closeConfirm()"
        class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
        Batal
      </button>

      {{-- Aktif setelah hitung mundur 10 detik selesai --}}
      <button type="button"
        wire:click="createBackup"
        x-on:click="closeConfirm()"
        x-bind:disabled="countdown > 0"
        class="inline-flex items-center justify-center gap-2 rounded bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
        <template x-if="countdown > 0">
          <span><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="countdown"></span>s</span>
        </template>
        <template x-if="countdown <= 0">
          <span><i class="fa-solid fa-database"></i> Ya, Buat Backup</span>
        </template>
      </button>
    </x-slot>
  </x-ui.modal>

  {{-- Modal proses: tampil selama backup diproses worker; tidak bisa ditutup,
       menutup sendiri saat $processing menjadi false (pollBackup). --}}
  <x-ui.modal show="$wire.processing" :closeable="false" title="Membuat Backup Database"
    description="Mohon tunggu, jangan tutup halaman ini" icon="fa-solid fa-database text-primary-600" maxWidth="max-w-sm">
    <div class="flex flex-col items-center gap-3 py-4 text-center">
      <div class="flex size-14 items-center justify-center rounded-full bg-primary-50 text-2xl text-primary-600">
        <i class="fa-solid fa-spinner fa-spin"></i>
      </div>
      <p class="text-sm font-semibold text-slate-700">Backup sedang diproses di latar belakang…</p>
      <p class="text-xs text-slate-500">Worker sedang membuat cadangan database. Jendela ini akan tertutup otomatis setelah selesai.</p>
    </div>
  </x-ui.modal>

  {{-- Modal konfirmasi hapus dengan jeda 10 detik --}}
  <x-ui.modal show="showDelete" :closeable="false" title="Konfirmasi Hapus Backup"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-triangle-exclamation text-red-600" maxWidth="max-w-md">
    <div class="space-y-3 text-sm text-slate-600">
      <p>File backup berikut akan <strong>dihapus permanen</strong>:</p>
      <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700 break-all" x-text="deleteTarget"></div>
      <div class="flex items-start gap-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
        <i class="fa-regular fa-clock mt-0.5"></i>
        <span>Demi keamanan, tombol hapus aktif setelah <strong>10 detik</strong>.</span>
      </div>
    </div>

    <x-slot name="footer" class="flex items-center justify-end gap-3 bg-slate-50">
      <button type="button" x-on:click="closeDelete()"
        class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
        Batal
      </button>

      {{-- Aktif setelah hitung mundur 10 detik selesai --}}
      <button type="button"
        x-on:click="$wire.deleteBackup(deleteTarget); closeDelete()"
        x-bind:disabled="deleteCountdown > 0"
        class="inline-flex items-center justify-center gap-2 rounded bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
        <template x-if="deleteCountdown > 0">
          <span><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="deleteCountdown"></span>s</span>
        </template>
        <template x-if="deleteCountdown <= 0">
          <span><i class="fa-solid fa-trash-can"></i> Ya, Hapus</span>
        </template>
      </button>
    </x-slot>
  </x-ui.modal>
</div>
