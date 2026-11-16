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
    showImport: false,
    importTarget: '',
    importCountdown: 10,
    importTimer: null,
    {{-- importTarget kosong = file unggahan; berisi nama = pulihkan dari daftar backup. --}}
    openImport(name) {
      this.importTarget = name;
      this.importCountdown = 10;
      this.showImport = true;
      clearInterval(this.importTimer);
      this.importTimer = setInterval(() => {
        if (this.importCountdown > 0) this.importCountdown--;
        if (this.importCountdown <= 0) clearInterval(this.importTimer);
      }, 1000);
    },
    closeImport() {
      this.showImport = false;
      clearInterval(this.importTimer);
    },
  }"
  x-on:open-backup-confirm.window="openConfirm()">

  {{-- Polling status backup yang sedang diproses worker --}}
  @if ($processing)
    <div wire:poll.2s="pollBackup"></div>
  @endif

  {{-- Header (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-database pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-shield-halved text-[9px]"></i>
          Cadangan Sistem
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ count($backups) }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Backup Database</h1>
        <p class="mt-1 text-xs text-slate-500">Buat dan unduh cadangan database sistem</p>
      </div>

      @if ($processing)
        <x-ui.button type="button" class="shrink-0" disabled>
          <i class="fa-solid fa-spinner fa-spin text-xs"></i> Memproses di worker...
        </x-ui.button>
      @else
        {{-- Cek worker dulu di server (requestBackup); modal baru dibuka bila worker sehat. --}}
        <x-ui.button type="button" class="shrink-0" wire:click="requestBackup" wire:loading.attr="disabled" wire:target="requestBackup">
          <x-slot name="icon"><i class="fa-solid fa-database text-xs"></i></x-slot>
          Buat Backup Sekarang
        </x-ui.button>
      @endif
    </div>
  </div>

  {{-- Info database --}}
  <div class="dash-enter grid grid-cols-1 gap-4 sm:grid-cols-3">
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
  <div class="dash-enter flex items-start gap-3 rounded border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
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
  <div class="dash-enter flex items-start gap-3 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
    <p>
      File berformat <strong>.sql.gz</strong> (SQL terkompresi). Simpan di tempat aman.
      @if ($importEnabled)
        <strong>Mode development:</strong> import dan pulihkan database tersedia di halaman ini.
        Di server produksi fitur tersebut tidak muncul; pemulihan dilakukan administrator melalui CLI.
      @else
        <strong>Restore</strong> sengaja tidak disediakan lewat aplikasi demi keamanan. Pemulihan
        sebaiknya dilakukan administrator melalui CLI pada jendela pemeliharaan terkontrol.
      @endif
    </p>
  </div>

  {{-- Import database: hanya mode development (gerbang juga dicek di server). --}}
  @if ($importEnabled)
    <div class="dash-enter rounded border border-red-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded bg-red-50 text-red-600"><i class="fa-solid fa-file-import"></i></div>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-sm font-bold text-slate-800">Import Database</h2>
            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">Mode Development</span>
          </div>
          <p class="mt-1 text-xs text-slate-500">
            Isi database <strong class="text-slate-700">{{ $database }}</strong> akan <strong class="text-red-600">diganti seluruhnya</strong>
            dengan isi file. Sebelum itu sistem membuat backup pengaman (<span class="font-mono">…-pre-import-…</span>) di daftar bawah,
            dan bila import gagal database dikembalikan otomatis.
          </p>
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
        <x-form.file wire:model="importFile" accept=".sql,.gz" wrapperClass="flex-1"
          hint="Format .sql atau .sql.gz (mis. hasil backup halaman ini), maksimal 100 MB." />

        <x-ui.button type="button" variant="danger" class="shrink-0"
          x-on:click="openImport('')" x-bind:disabled="! $wire.importFile"
          wire:loading.attr="disabled" wire:target="importFile">
          <x-slot name="icon"><i class="fa-solid fa-file-import text-xs"></i></x-slot>
          Import Database
        </x-ui.button>
      </div>
    </div>
  @endif

  {{-- Daftar backup --}}
  <div class="dash-enter table-wrapper">
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
                @if ($importEnabled)
                  <button type="button" x-on:click="openImport('{{ $backup['name'] }}')"
                    class="inline-flex items-center gap-1.5 rounded border border-amber-500 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-500 hover:text-white">
                    <i class="fa-solid fa-clock-rotate-left"></i> Pulihkan
                  </button>
                @endif
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

  @if ($importEnabled)
    {{-- Modal konfirmasi import/pulihkan dengan jeda 10 detik --}}
    <x-ui.modal show="showImport" :closeable="false" title="Konfirmasi Import Database"
      description="Seluruh isi database akan ditimpa" icon="fa-solid fa-triangle-exclamation text-red-600" maxWidth="max-w-md">
      <div class="space-y-3 text-sm text-slate-600">
        <p>Semua tabel di database <strong>{{ $database }}</strong> akan <strong>dihapus</strong> lalu diisi dari:</p>
        <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700 break-all"
          x-text="importTarget || 'file yang Anda unggah'"></div>
        <p class="text-xs text-slate-500">Bila akun Anda tidak ada di data baru, Anda akan diminta masuk kembali.</p>
        <div class="flex items-start gap-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
          <i class="fa-regular fa-clock mt-0.5"></i>
          <span>Demi keamanan, tombol import aktif setelah <strong>10 detik</strong>.</span>
        </div>
      </div>

      <x-slot name="footer" class="flex items-center justify-end gap-3 bg-slate-50">
        <button type="button" x-on:click="closeImport()"
          class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
          Batal
        </button>

        <button type="button"
          x-on:click="importTarget ? $wire.restoreBackup(importTarget) : $wire.importDatabase(); closeImport()"
          x-bind:disabled="importCountdown > 0"
          class="inline-flex items-center justify-center gap-2 rounded bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
          <template x-if="importCountdown > 0">
            <span><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="importCountdown"></span>s</span>
          </template>
          <template x-if="importCountdown <= 0">
            <span><i class="fa-solid fa-file-import"></i> Ya, Timpa Database</span>
          </template>
        </button>
      </x-slot>
    </x-ui.modal>

    {{-- Lapisan proses selama import berjalan (request sinkron; jangan tutup halaman).
         Di-teleport ke body seperti x-ui.modal: di dalam <main> lapisan ini terjebak di
         stacking context konten sehingga header & sidebar tetap tampil di atasnya. --}}
    <template x-teleport="body">
      <div wire:loading.flex wire:target="importDatabase,restoreBackup"
        class="fixed inset-0 z-[70] items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div role="status" aria-live="polite"
          class="flex w-full max-w-sm flex-col items-center gap-3 rounded border border-slate-200 bg-white px-6 py-8 text-center shadow-xl">
          <div class="flex size-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-600">
            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
          </div>
          <p class="text-sm font-semibold text-slate-700">Mengimport database…</p>
          <p class="text-xs text-slate-500">Membuat backup pengaman, menghapus tabel lama, lalu memuat data baru. Jangan tutup halaman ini.</p>
        </div>
      </div>
    </template>
  @endif

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
