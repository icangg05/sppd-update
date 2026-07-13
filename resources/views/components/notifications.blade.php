@php
  $items = config('changelog.items', []);
  $version = config('changelog.version', '');
  $count = count($items);

  // Status "belum dibaca" per-user: dibandingkan dengan versi yang terakhir
  // dilihat user (tersimpan di DB), sehingga sinkron di semua perangkat.
  $unread = ($count > 0) && (auth()->user()?->changelog_seen_version !== $version);
  $seenUrl = route('notifications.seen');

  // Peta token warna -> kelas chip ikon (ditulis literal agar terpindai Tailwind).
  $chipColors = [
    'emerald' => 'bg-emerald-50 text-emerald-600',
    'cyan' => 'bg-primary-50 text-primary-600',
    'indigo' => 'bg-indigo-50 text-indigo-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'sky' => 'bg-sky-50 text-sky-600',
    'violet' => 'bg-violet-50 text-violet-600',
    'slate' => 'bg-slate-100 text-slate-600',
    'rose' => 'bg-rose-50 text-rose-600',
  ];

  // Warna kartu unggulan per token (literal agar terpindai Tailwind).
  $featuredColors = [
    'emerald' => ['wrap' => 'from-emerald-50 ring-emerald-100', 'bar' => 'bg-emerald-500', 'chip' => 'bg-emerald-500 shadow-emerald-500/30', 'badge' => 'bg-emerald-600', 'btn' => 'bg-emerald-600 hover:bg-emerald-700'],
    'cyan' => ['wrap' => 'from-primary-50 ring-primary-100', 'bar' => 'bg-primary-500', 'chip' => 'bg-primary-600 shadow-primary-500/30', 'badge' => 'bg-primary-600', 'btn' => 'bg-primary-600 hover:bg-primary-700'],
  ];
@endphp

<div x-data="notificationCenter({{ $count }}, {{ $unread ? 'true' : 'false' }}, @js($seenUrl))" class="relative"
  {{-- Kunci scroll halaman saat panel dibuka di mobile; scroll di dalam panel tetap jalan. --}}
  x-effect="document.body.classList.toggle('overflow-hidden', open && window.matchMedia('(max-width: 639px)').matches)">
  {{-- Tombol lonceng --}}
  <button type="button" @click="toggle()"
    class="relative inline-flex size-9 items-center justify-center rounded-full bg-primary-600/30 text-white transition-all hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-white/40"
    aria-label="Pembaruan sistem" title="Pembaruan sistem">
    <i class="fa-regular fa-bell"></i>
    {{-- Badge merah per-user: muncul saat ada pembaruan baru utk user ini, hilang setelah dibuka --}}
    <span x-show="unread" x-cloak
      class="absolute -right-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full border-2 border-primary-700 bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
      x-text="count"></span>
  </button>

  {{-- Backdrop (mobile fullscreen) --}}
  <div x-show="open" x-cloak x-transition.opacity @click="close()"
    class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm sm:hidden"></div>

  {{-- Panel: fullscreen di mobile, dropdown di desktop --}}
  <div x-show="open" x-cloak @click.outside="close()" @keydown.escape.window="close()"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2 sm:-translate-y-2 sm:scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave-end="opacity-0 translate-y-2 sm:-translate-y-2 sm:scale-95"
    class="fixed inset-0 z-50 flex flex-col bg-white text-slate-800
           sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-3 sm:h-auto sm:max-h-[85vh] sm:w-96
           sm:rounded sm:border sm:border-slate-200 sm:shadow-2xl sm:shadow-slate-900/10 sm:ring-1 sm:ring-black/5">

    {{-- Header panel --}}
    <div class="flex items-center justify-between gap-3 bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3.5 text-white sm:rounded-t">
      <div class="leading-tight">
        <p class="flex items-center gap-2 text-sm font-bold">
          <i class="fa-solid fa-bullhorn text-primary-100"></i> Pembaruan Sistem
        </p>
        <p class="text-[11px] text-primary-100">{{ $count }} pembaruan terbaru</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="hidden rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold tracking-wide sm:inline">
          v{{ config('app.sppd_version', $version) }}
        </span>
        {{-- Tombol tutup (mobile) --}}
        <button type="button" @click="close()"
          class="inline-flex size-8 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 sm:hidden"
          aria-label="Tutup">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>

    {{-- Daftar pembaruan --}}
    <div class="custom-scrollbar flex-1 divide-y divide-slate-100 overflow-y-auto sm:max-h-96 sm:flex-none">
      @forelse ($items as $item)
        @php $chip = $chipColors[$item['color'] ?? 'slate'] ?? $chipColors['slate']; @endphp

        @if (!empty($item['featured']))
          @php $fc = $featuredColors[$item['color'] ?? 'emerald'] ?? $featuredColors['emerald']; @endphp
          {{-- Item unggulan: disorot --}}
          <div class="relative bg-gradient-to-br to-white px-4 py-4 ring-1 ring-inset {{ $fc['wrap'] }}">
            <span class="absolute left-0 top-0 h-full w-1 {{ $fc['bar'] }}"></span>
            <div class="flex gap-3">
              <div class="flex size-11 shrink-0 items-center justify-center rounded text-lg text-white shadow-sm {{ $fc['chip'] }}">
                <i class="{{ $item['icon'] ?? 'fa-brands fa-whatsapp' }}"></i>
              </div>
              <div class="min-w-0 flex-1">
                <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-800">
                  {{ $item['title'] }}
                  @if (!empty($item['badge']))
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold text-white {{ $fc['badge'] }}">
                      <i class="fa-solid fa-star text-[8px]"></i> {{ $item['badge'] }}
                    </span>
                  @endif
                </p>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $item['description'] }}</p>

                @if (!empty($item['guide']))
                  <a href="{{ route($item['guide']) }}" wire:navigate @click="closeForNav()"
                    class="mt-2.5 inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition {{ $fc['btn'] }}">
                    <i class="fa-solid fa-book-open text-[11px]"></i> {{ $item['guide_label'] ?? 'Buka Panduan' }}
                  </a>
                @endif

                <p class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                  <i class="fa-solid fa-circle-check"></i> Sudah aktif
                </p>
              </div>
            </div>
          </div>
        @else
          {{-- Item biasa --}}
          <div class="flex gap-3 px-4 py-3 transition-colors hover:bg-slate-50">
            <div class="flex size-9 shrink-0 items-center justify-center rounded {{ $chip }}">
              <i class="{{ $item['icon'] ?? 'fa-solid fa-star' }} text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
              <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ $item['description'] }}</p>
              <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                <i class="fa-solid fa-circle-check"></i> Sudah aktif
              </p>
            </div>
          </div>
        @endif
      @empty
        <div class="px-4 py-10 text-center text-sm text-slate-500">
          <i class="fa-regular fa-bell-slash mb-2 block text-2xl"></i>
          Belum ada pembaruan.
        </div>
      @endforelse
    </div>

    {{-- Footer --}}
    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3 text-center sm:rounded-b">
      <span class="text-[11px] text-slate-500">Sistem Perjalanan Dinas — Kota Kendari</span>
    </div>
  </div>
</div>

@once
  @push('scripts')
    <script>
      function notificationCenter(count, unread, seenUrl) {
        return {
          open: false,
          count: count,
          unread: unread, // status awal per-user dari server
          seenUrl: seenUrl,
          _pushed: false, // menandai entri history yang kita sisipkan (mobile)
          init() {
            // Tombol Kembali (mobile) menutup panel, bukan pindah halaman.
            window.addEventListener('popstate', () => {
              if (this.open) {
                this._pushed = false; // entri sudah dikonsumsi oleh aksi back
                this.open = false;
              }
            });
          },
          isMobile() {
            return window.matchMedia('(max-width: 639px)').matches;
          },
          toggle() {
            this.open ? this.close() : this.openPanel();
          },
          openPanel() {
            this.open = true;
            this.markSeen();
            // Sisipkan entri history di mobile agar Kembali menutup panel.
            if (this.isMobile()) {
              history.pushState({ notif: true }, '');
              this._pushed = true;
            }
          },
          close() {
            if (!this.open) return;
            this.open = false;
            if (this._pushed) {
              this._pushed = false;
              history.back(); // buang entri yang kita sisipkan
            }
          },
          closeForNav() {
            // Menutup lalu berpindah halaman (link panduan): serahkan history
            // ke navigasi SPA, jangan panggil history.back().
            this.open = false;
            this._pushed = false;
          },
          markSeen() {
            if (!this.unread) return;
            this.unread = false; // sembunyikan badge langsung (optimistic)
            // Simpan ke server agar sinkron di semua perangkat user ini.
            fetch(this.seenUrl, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
              },
            }).catch(() => {});
          },
        };
      }
    </script>
  @endpush
@endonce
