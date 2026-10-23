@props([
    // Nama property Livewire untuk tanggal awal & akhir (format Y-m-d).
    'start',
    'end',
    'label' => null,
    'placeholder' => 'Pilih tanggal',
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

{{-- Satu field rentang tanggal: klik tanggal awal, lalu tanggal akhir di kalender.
     Klik tanggal yang sama dua kali untuk rentang satu hari. Nilai baru dikirim ke
     Livewire setelah rentang lengkap; menutup panel di tengah jalan membatalkannya. --}}
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <div
        x-data="{
            open: false,
            start: @entangle($start),
            end: @entangle($end),
            // Tanggal awal yang sudah diklik, menunggu klik tanggal akhir.
            draft: null,
            hover: null,
            view: { y: 0, m: 0 },
            count: 1,
            dropUp: false,
            coords: { top: 0, left: 0 },
            monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            monthsLong: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            today: '',
            init() {
                const t = new Date();
                this.today = this.iso(t.getFullYear(), t.getMonth(), t.getDate());
            },
            // Tanggal diolah sebagai string Y-m-d (zona lokal) agar tidak bergeser
            // karena konversi UTC; string ini juga bisa dibandingkan langsung.
            iso(y, m, d) {
                return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            },
            parse(s) {
                const [y, m, d] = String(s).split('-').map(Number);
                return { y, m: m - 1, d };
            },
            get summary() {
                if (!this.start) return '';
                const s = this.parse(this.start);
                const e = this.parse(this.end || this.start);
                const full = (p) => p.d + ' ' + this.monthsShort[p.m] + ' ' + p.y;
                if (this.start === (this.end || this.start)) return full(s);
                if (s.y === e.y && s.m === e.m) return s.d + '–' + full(e);
                if (s.y === e.y) return s.d + ' ' + this.monthsShort[s.m] + ' – ' + full(e);
                return full(s) + ' – ' + full(e);
            },
            get days() {
                if (!this.start) return 0;
                const s = this.parse(this.start);
                const e = this.parse(this.end || this.start);
                return Math.round((Date.UTC(e.y, e.m, e.d) - Date.UTC(s.y, s.m, s.d)) / 86400000) + 1;
            },
            get months() {
                const list = [];
                for (let i = 0; i < this.count; i++) {
                    const d = new Date(this.view.y, this.view.m + i, 1);
                    list.push({ y: d.getFullYear(), m: d.getMonth() });
                }
                return list;
            },
            // Jumlah baris disamakan antarbulan yang tampil agar kalender sejajar.
            get rows() {
                return Math.max(...this.months.map(({ y, m }) => {
                    const lead = (new Date(y, m, 1).getDay() + 6) % 7;
                    return Math.ceil((lead + new Date(y, m + 1, 0).getDate()) / 7);
                }));
            },
            cells(y, m) {
                const lead = (new Date(y, m, 1).getDay() + 6) % 7; // Senin di kolom pertama
                const total = new Date(y, m + 1, 0).getDate();
                const list = [];
                for (let i = 0; i < this.rows * 7; i++) {
                    const d = i - lead + 1;
                    list.push(d >= 1 && d <= total
                        ? { key: this.iso(y, m, d), iso: this.iso(y, m, d), day: d, dow: (i + 1) % 7 }
                        : { key: 'blank-' + i, iso: null });
                }
                return list;
            },
            // Rentang yang disorot: pilihan tersimpan, atau pratinjau saat memilih.
            get from() {
                return this.draft ?? this.start;
            },
            get to() {
                if (this.draft) return this.hover && this.hover > this.draft ? this.hover : this.draft;
                return this.end || this.start;
            },
            bandClass(iso) {
                if (!this.from || this.from === this.to) return '';
                if (iso > this.from && iso < this.to) return 'bg-primary-50';
                if (iso === this.from) return 'bg-linear-to-r from-transparent from-50% to-primary-50 to-50%';
                if (iso === this.to) return 'bg-linear-to-l from-transparent from-50% to-primary-50 to-50%';
                return '';
            },
            dayClass(cell) {
                const iso = cell.iso;
                if (iso === this.from || (iso === this.to && !this.draft)) return 'bg-primary-600 font-semibold text-white hover:bg-primary-700';
                if (iso === this.to) return 'bg-primary-100 font-semibold text-primary-800';
                if (iso > this.from && iso < this.to) return 'text-primary-800 hover:bg-primary-100';
                const tone = cell.dow === 0 ? 'text-rose-600' : 'text-slate-700';
                return iso === this.today
                    ? tone + ' font-semibold ring-1 ring-inset ring-primary-400 hover:bg-slate-100'
                    : tone + ' hover:bg-slate-100';
            },
            dayLabel(cell) {
                const p = this.parse(cell.iso);
                return this.dayNames[cell.dow] + ', ' + p.d + ' ' + this.monthsLong[p.m] + ' ' + p.y;
            },
            pick(iso) {
                if (!this.draft || iso < this.draft) {
                    this.draft = iso;
                    this.hover = null;
                    return;
                }
                this.start = this.draft;
                this.end = iso;
                this.close();
            },
            shift(n) {
                const d = new Date(this.view.y, this.view.m + n, 1);
                this.view = { y: d.getFullYear(), m: d.getMonth() };
                // Jumlah baris bisa berubah; posisikan ulang (penting saat panel membuka ke atas).
                this.$nextTick(() => this.position());
            },
            toggle() {
                if (this.open) return this.close();
                this.count = window.innerWidth >= 640 ? 2 : 1;
                const base = this.parse(this.start || this.today);
                this.view = { y: base.y, m: base.m };
                this.draft = null;
                this.hover = null;
                this.open = true;
                this.$nextTick(() => this.position());
            },
            close() {
                this.open = false;
                this.draft = null;
                this.hover = null;
            },
            position() {
                const r = this.$refs.trigger.getBoundingClientRect();
                const margin = 8;
                const panel = this.$refs.panel;
                const w = panel ? panel.offsetWidth : 300;
                const h = panel ? panel.offsetHeight : 340;
                const spaceBelow = window.innerHeight - r.bottom;
                this.dropUp = spaceBelow < h + 4 && r.top > spaceBelow;
                this.coords = {
                    top: this.dropUp ? Math.max(margin, r.top - h - 4) : r.bottom + 4,
                    // Rata kiri dengan field, tapi tidak keluar dari layar.
                    left: Math.max(margin, Math.min(r.left, window.innerWidth - w - margin)),
                };
            },
            onOutside(e) {
                if (!this.open) return;
                // Elemen yang sudah dilepas dari DOM oleh render ulang (mis. sel tanggal
                // bulan lama) bukan klik di luar panel; sama seperti Alpine .outside.
                if (!e.target.isConnected) return;
                if (this.$refs.trigger.contains(e.target)) return;
                if (this.$refs.panel && this.$refs.panel.contains(e.target)) return;
                this.close();
            },
        }"
        @keydown.escape.window="if (open) close()"
        @click.window="onOutside($event)"
        @scroll.window.capture="if (open) position()"
        @resize.window="if (open) position()"
        class="relative">

        {{-- Proxy validasi native (lihat searchable-select): tahan submit bila kosong. --}}
        @if ($required)
            <input type="text" tabindex="-1" aria-hidden="true" inputmode="none" autocomplete="off" required
                x-bind:value="start && end ? start + '/' + end : ''"
                x-effect="$el.setCustomValidity(start && end ? '' : 'Silakan pilih tanggal awal dan akhir.')"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-px w-full opacity-0">
        @endif

        <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open" aria-haspopup="dialog"
            @class([
                'flex w-full items-center gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                $class,
            ])>
            <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
            <span class="min-w-0 flex-1 truncate text-left" :class="summary ? 'text-slate-800' : 'text-slate-500'"
                x-text="summary || @js($placeholder)"></span>
            <span x-show="days > 0" x-cloak x-text="days + ' hari'"
                class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600"></span>
        </button>

        <template x-teleport="body">
            <div x-ref="panel" x-show="open" x-cloak role="dialog" aria-label="Pilih rentang tanggal"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :style="`position: fixed; top: ${coords.top}px; left: ${coords.left}px; z-index: 9999; transform-origin: ${dropUp ? 'bottom' : 'top'} left;`"
                class="rounded border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
                <div class="flex gap-6 p-3" @mouseleave="hover = null">
                    {{-- Key per posisi (bukan per bulan) agar blok & tombol navigasi tidak dibuat
                         ulang saat bulan digeser; isinya tetap ikut berganti. --}}
                    <template x-for="(month, mi) in months" :key="mi">
                        <div class="w-63">
                            <div class="mb-2 flex items-center justify-between">
                                <button type="button" @click="shift(-1)" :class="mi === 0 ? '' : 'invisible'"
                                    class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Bulan sebelumnya">
                                    <i class="fa-solid fa-chevron-left text-xs"></i>
                                </button>
                                <span class="text-sm font-semibold text-slate-800"
                                    x-text="monthsLong[month.m] + ' ' + month.y"></span>
                                <button type="button" @click="shift(1)" :class="mi === months.length - 1 ? '' : 'invisible'"
                                    class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Bulan berikutnya">
                                    <i class="fa-solid fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-slate-500">
                                <template x-for="(d, di) in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d">
                                    <div class="py-1" :class="di === 6 && 'text-rose-500'" x-text="d"></div>
                                </template>
                            </div>
                            <div class="grid grid-cols-7">
                                <template x-for="cell in cells(month.y, month.m)" :key="cell.key">
                                    <div class="py-0.5" :class="cell.iso && bandClass(cell.iso)">
                                        <template x-if="cell.iso">
                                            <button type="button" @click="pick(cell.iso)" @mouseenter="if (draft) hover = cell.iso"
                                                :aria-label="dayLabel(cell)" :aria-pressed="cell.iso === from || cell.iso === to"
                                                class="mx-auto flex size-9 items-center justify-center rounded text-sm tabular-nums transition-colors"
                                                :class="dayClass(cell)" x-text="cell.day"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                {{-- w-0 min-w-full: teks petunjuk mengikuti lebar kalender, tidak melebarkan panel. --}}
                <div class="w-0 min-w-full border-t border-slate-100 px-3 py-2 text-xs text-slate-600">
                    <template x-if="!draft">
                        <span>Klik <strong class="font-semibold text-slate-800">tanggal berangkat</strong>, lalu tanggal kembali.</span>
                    </template>
                    <template x-if="draft">
                        <span>Klik <strong class="font-semibold text-slate-800">tanggal kembali</strong>. Klik tanggal yang sama untuk perjalanan satu hari.</span>
                    </template>
                </div>
            </div>
        </template>
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($start)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
    @error($end)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
