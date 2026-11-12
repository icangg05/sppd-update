@props([
    // Dua property Livewire (format Y-m-d) yang diisi dari satu field, mis. tanggal SPT & SPPD.
    'first',
    'second',
    // Nama singkat tiap tanggal, tampil di ringkasan field dan judul kalendernya.
    'firstLabel' => 'Tanggal 1',
    'secondLabel' => 'Tanggal 2',
    'label' => null,
    'required' => false,
    'hint' => null,
    'labelClass' => '',
    'wrapperClass' => '',
])

{{-- Satu field berisi dua tanggal terpisah. Panelnya menampilkan dua kalender berdampingan
     (bertumpuk di layar sempit), masing-masing dengan judul & navigasi bulan sendiri: klik
     tanggal di kalender pertama untuk tanggal pertama, kalender kedua untuk tanggal kedua.
     Panel menutup sendiri setelah kedua kalender diklik. Untuk satu rentang, pakai x-form.date-range. --}}
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
            a: @entangle($first),
            b: @entangle($second),
            labels: @js([$firstLabel, $secondLabel]),
            // Bulan yang tampil di tiap kalender; digeser sendiri-sendiri.
            views: [{ y: 0, m: 0 }, { y: 0, m: 0 }],
            // Kalender yang sudah diklik sejak panel dibuka; keduanya = panel ditutup.
            touched: [false, false],
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
            // Tanggal diolah sebagai string Y-m-d (zona lokal) agar tidak bergeser karena UTC.
            iso(y, m, d) {
                return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            },
            parse(s) {
                const [y, m, d] = String(s).split('-').map(Number);
                return { y, m: m - 1, d };
            },
            value(i) {
                return i === 0 ? this.a : this.b;
            },
            format(s) {
                if (!s) return null;
                const p = this.parse(s);
                return p.d + ' ' + this.monthsShort[p.m] + ' ' + p.y;
            },
            rowsOf(i) {
                const { y, m } = this.views[i];
                const lead = (new Date(y, m, 1).getDay() + 6) % 7;
                return Math.ceil((lead + new Date(y, m + 1, 0).getDate()) / 7);
            },
            // Jumlah baris disamakan antarkalender agar keduanya sejajar.
            get rows() {
                return Math.max(this.rowsOf(0), this.rowsOf(1));
            },
            cells(i) {
                const { y, m } = this.views[i];
                const lead = (new Date(y, m, 1).getDay() + 6) % 7; // Senin di kolom pertama
                const total = new Date(y, m + 1, 0).getDate();
                const list = [];
                for (let k = 0; k < this.rows * 7; k++) {
                    const d = k - lead + 1;
                    list.push(d >= 1 && d <= total
                        ? { key: this.iso(y, m, d), iso: this.iso(y, m, d), day: d, dow: (k + 1) % 7 }
                        : { key: 'blank-' + k, iso: null });
                }
                return list;
            },
            dayClass(i, cell) {
                if (cell.iso === this.value(i)) return 'bg-primary-600 font-semibold text-white hover:bg-primary-700';
                const tone = cell.dow === 0 ? 'text-rose-600' : 'text-slate-700';
                return cell.iso === this.today
                    ? tone + ' font-semibold ring-1 ring-inset ring-primary-400 hover:bg-slate-100'
                    : tone + ' hover:bg-slate-100';
            },
            dayLabel(i, cell) {
                const p = this.parse(cell.iso);
                return this.labels[i] + ': ' + this.dayNames[cell.dow] + ', ' + p.d + ' ' + this.monthsLong[p.m] + ' ' + p.y;
            },
            pick(i, iso) {
                if (i === 0) this.a = iso; else this.b = iso;
                this.touched[i] = true;
                if (this.touched[0] && this.touched[1]) this.close();
            },
            pickToday() {
                this.a = this.today;
                this.b = this.today;
                this.close();
            },
            shift(i, n) {
                const d = new Date(this.views[i].y, this.views[i].m + n, 1);
                this.views[i] = { y: d.getFullYear(), m: d.getMonth() };
                // Jumlah baris bisa berubah; posisikan ulang (penting saat panel membuka ke atas).
                this.$nextTick(() => this.position());
            },
            toggle() {
                if (this.open) return this.close();
                const a = this.parse(this.a || this.today);
                const b = this.parse(this.b || this.a || this.today);
                this.views = [{ y: a.y, m: a.m }, { y: b.y, m: b.m }];
                this.touched = [false, false];
                this.open = true;
                this.$nextTick(() => this.position());
            },
            close() {
                this.open = false;
            },
            position() {
                const r = this.$refs.trigger.getBoundingClientRect();
                const margin = 8;
                const panel = this.$refs.panel;
                const w = panel ? panel.offsetWidth : 560;
                const h = panel ? panel.offsetHeight : 380;
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

        {{-- Proxy validasi native (lihat searchable-select): tahan submit bila salah satu kosong. --}}
        @if ($required)
            <input type="text" tabindex="-1" aria-hidden="true" inputmode="none" autocomplete="off" required
                x-bind:value="a && b ? a + '/' + b : ''"
                x-effect="$el.setCustomValidity(a && b ? '' : 'Silakan pilih tanggal ' + labels[0] + ' dan ' + labels[1] + '.')"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-px w-full opacity-0">
        @endif

        <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open" aria-haspopup="dialog"
            class="flex w-full items-center gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
            <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
            <span class="flex min-w-0 flex-1 flex-wrap items-center gap-x-4 gap-y-1 text-left">
                <template x-for="i in [0, 1]" :key="i">
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600" x-text="labels[i]"></span>
                        <span :class="value(i) ? 'text-slate-800' : 'text-slate-500'" x-text="format(value(i)) ?? 'Pilih tanggal'"></span>
                    </span>
                </template>
            </span>
        </button>

        <template x-teleport="body">
            <div x-ref="panel" x-show="open" x-cloak role="dialog" aria-label="{{ 'Pilih tanggal ' . $firstLabel . ' dan ' . $secondLabel }}"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :style="`position: fixed; top: ${coords.top}px; left: ${coords.left}px; z-index: 9999; transform-origin: ${dropUp ? 'bottom' : 'top'} left;`"
                class="max-h-[calc(100vh-16px)] overflow-y-auto rounded border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
                <div class="flex flex-col gap-4 p-3 sm:flex-row sm:gap-0">
                    <template x-for="i in [0, 1]" :key="i">
                        {{-- box-content: garis & jarak pemisah kalender kedua tidak memakan lebar kalendernya. --}}
                        <div class="box-content w-63" :class="i === 1 && 'border-t border-slate-100 pt-4 sm:ml-5 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-5'">
                            {{-- Judul kalender: dokumen apa dan tanggal yang terpilih. --}}
                            <div class="mb-2 flex items-center justify-between gap-2 rounded bg-primary-50 px-2.5 py-1.5">
                                <span class="text-[11px] font-bold tracking-wide text-primary-800 uppercase" x-text="'Tanggal ' + labels[i]"></span>
                                <span class="text-xs font-semibold tabular-nums" :class="value(i) ? 'text-primary-900' : 'text-primary-700/70'"
                                    x-text="format(value(i)) ?? 'Belum dipilih'"></span>
                            </div>
                            <div class="mb-2 flex items-center justify-between">
                                <button type="button" @click="shift(i, -1)"
                                    class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                    :aria-label="'Bulan sebelumnya, kalender ' + labels[i]">
                                    <i class="fa-solid fa-chevron-left text-xs"></i>
                                </button>
                                <span class="text-sm font-semibold text-slate-800"
                                    x-text="monthsLong[views[i].m] + ' ' + views[i].y"></span>
                                <button type="button" @click="shift(i, 1)"
                                    class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                    :aria-label="'Bulan berikutnya, kalender ' + labels[i]">
                                    <i class="fa-solid fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-slate-500">
                                <template x-for="(d, di) in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d">
                                    <div class="py-1" :class="di === 6 && 'text-rose-500'" x-text="d"></div>
                                </template>
                            </div>
                            <div class="grid grid-cols-7">
                                <template x-for="cell in cells(i)" :key="cell.key">
                                    <div class="py-0.5">
                                        <template x-if="cell.iso">
                                            <button type="button" @click="pick(i, cell.iso)"
                                                :aria-label="dayLabel(i, cell)" :aria-pressed="cell.iso === value(i)"
                                                class="mx-auto flex size-9 items-center justify-center rounded text-sm tabular-nums transition-colors"
                                                :class="dayClass(i, cell)" x-text="cell.day"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                {{-- w-0 min-w-full: baris ini mengikuti lebar kalender, tidak melebarkan panel di HP. --}}
                <div class="flex w-0 min-w-full flex-wrap items-center justify-between gap-x-3 gap-y-1.5 border-t border-slate-100 px-3 py-2 text-xs text-slate-600">
                    <span>Klik tanggal {{ $firstLabel }} dan tanggal {{ $secondLabel }} pada kalendernya masing-masing.</span>
                    <span class="flex shrink-0 items-center gap-1">
                        <button type="button" @click="pickToday()"
                            class="rounded px-2 py-1 font-semibold text-primary-700 transition hover:bg-primary-50">
                            Keduanya hari ini
                        </button>
                        <button type="button" @click="close()"
                            class="rounded bg-primary-600 px-2.5 py-1 font-semibold text-white transition hover:bg-primary-700">
                            Selesai
                        </button>
                    </span>
                </div>
            </div>
        </template>
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($first)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
    @error($second)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
