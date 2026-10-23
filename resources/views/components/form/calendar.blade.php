@props([
    'label' => null,
    'required' => false,
    'disabled' => false,
    // Tanggal paling awal yang boleh dipilih (Y-m-d); tanggal sebelumnya nonaktif.
    'min' => null,
    'minMessage' => 'Tanggal ini belum bisa dipilih.',
    // Warna aksen: 'primary' (default) atau 'emerald' (mis. halaman laporan).
    'accent' => 'primary',
    'hint' => null,
    'wrapperClass' => '',
])

{{-- Kalender inline (selalu tampil) untuk memilih satu tanggal (Y-m-d) via wire:model.
     Untuk rentang tanggal dalam panel lipat, pakai x-form.date-range. --}}
@php
    $model = $attributes->wire('model');
    $key = $model->value();
    $tones = [
        'primary' => [
            'selected' => 'bg-primary-600 font-semibold text-white hover:bg-primary-700',
            'today' => 'ring-primary-400',
            'focus' => 'focus-visible:ring-primary-500/40',
            'link' => 'text-primary-700 hover:bg-primary-50',
        ],
        'emerald' => [
            'selected' => 'bg-emerald-600 font-semibold text-white hover:bg-emerald-700',
            'today' => 'ring-emerald-400',
            'focus' => 'focus-visible:ring-emerald-500/40',
            'link' => 'text-emerald-700 hover:bg-emerald-50',
        ],
    ];
    $tone = $tones[$accent] ?? $tones['primary'];
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <p id="{{ $key }}-label" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
        </p>
    @endif

    <div
        x-data="{
            value: @entangle($model),
            view: { y: 0, m: 0 },
            today: '',
            disabled: @js((bool) $disabled),
            min: @js($min),
            monthsLong: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            init() {
                const t = new Date();
                this.today = this.iso(t.getFullYear(), t.getMonth(), t.getDate());
                this.showMonthOf(this.value || (this.isOff(this.today) ? this.min : this.today));
                // Nilai diubah dari server (mis. reset): tampilkan bulannya.
                this.$watch('value', (v) => v && this.showMonthOf(v));
            },
            // Tanggal diolah sebagai string Y-m-d (zona lokal) agar tidak bergeser karena UTC.
            iso(y, m, d) {
                return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            },
            parse(s) {
                const [y, m, d] = String(s).split('-').map(Number);
                return { y, m: m - 1, d };
            },
            showMonthOf(s) {
                const p = this.parse(s);
                this.view = { y: p.y, m: p.m };
            },
            isOff(iso) {
                return !!this.min && iso < this.min;
            },
            // Bulan sebelumnya hanya bila masih memuat tanggal yang boleh dipilih.
            get canPrev() {
                if (!this.min) return true;
                const p = this.parse(this.min);
                return this.view.y * 12 + this.view.m > p.y * 12 + p.m;
            },
            validity() {
                if (!this.value) return @js($required) ? 'Silakan pilih tanggal.' : '';
                return this.isOff(this.value) ? @js($minMessage) : '';
            },
            shift(n) {
                const d = new Date(this.view.y, this.view.m + n, 1);
                this.view = { y: d.getFullYear(), m: d.getMonth() };
            },
            // Selalu 6 baris (diisi tanggal bulan sebelum/sesudah) agar tinggi kalender tetap.
            get cells() {
                const { y, m } = this.view;
                const lead = (new Date(y, m, 1).getDay() + 6) % 7; // Senin di kolom pertama
                const list = [];
                for (let i = 0; i < 42; i++) {
                    const date = new Date(y, m, i - lead + 1);
                    const iso = this.iso(date.getFullYear(), date.getMonth(), date.getDate());
                    list.push({ iso, day: date.getDate(), dow: (i + 1) % 7, outside: date.getMonth() !== m });
                }
                return list;
            },
            dayClass(cell) {
                if (cell.iso === this.value) return @js($tone['selected']);
                if (this.isOff(cell.iso)) return 'cursor-not-allowed text-slate-300 line-through';
                if (cell.outside) return this.disabled ? 'text-slate-300' : 'text-slate-300 hover:bg-slate-50 hover:text-slate-500';
                const tone = cell.dow === 0 ? 'text-rose-600' : 'text-slate-700';
                const hover = this.disabled ? '' : ' hover:bg-slate-100';
                return cell.iso === this.today
                    ? tone + hover + ' font-semibold ring-1 ring-inset ' + @js($tone['today'])
                    : tone + hover;
            },
            label(s) {
                const p = this.parse(s);
                const dow = new Date(p.y, p.m, p.d).getDay();
                return this.dayNames[dow] + ', ' + p.d + ' ' + this.monthsLong[p.m] + ' ' + p.y;
            },
            pick(iso) {
                if (!this.disabled && !this.isOff(iso)) this.value = iso;
            },
            pickToday() {
                this.pick(this.today);
                this.showMonthOf(this.today);
            },
        }"
        role="group" @if ($label) aria-labelledby="{{ $key }}-label" @endif
        class="relative rounded border border-slate-200 bg-white p-3 shadow-2xs">

        {{-- Proxy validasi native (lihat searchable-select): tahan submit bila kosong. --}}
        @if ($required || $min)
            <input type="text" tabindex="-1" aria-hidden="true" inputmode="none" autocomplete="off" @required($required)
                @if ($disabled) disabled @endif
                x-bind:value="value || ''"
                x-effect="$el.setCustomValidity(validity())"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-px w-full opacity-0">
        @endif

        <div class="mb-2 flex items-center justify-between">
            <button type="button" @click="shift(-1)" :disabled="!canPrev" aria-label="Bulan sebelumnya"
                class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <span class="text-sm font-semibold text-slate-800" aria-live="polite"
                x-text="monthsLong[view.m] + ' ' + view.y"></span>
            <button type="button" @click="shift(1)" aria-label="Bulan berikutnya"
                class="flex size-8 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
        </div>

        <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-slate-500">
            <template x-for="(d, di) in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d">
                <div class="py-1" :class="di === 6 && 'text-rose-500'" x-text="d"></div>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-y-0.5">
            {{-- Tanggal bulan lain ikut bisa dipilih; $watch value lalu memindah tampilan bulannya. --}}
            <template x-for="cell in cells" :key="cell.iso">
                <div class="flex justify-center">
                    <button type="button" @click="pick(cell.iso)" :disabled="disabled || isOff(cell.iso)"
                        :aria-label="label(cell.iso)" :aria-pressed="cell.iso === value"
                        class="flex h-9 w-full max-w-10 items-center justify-center rounded text-sm tabular-nums transition-colors focus:outline-none focus-visible:ring-2 disabled:cursor-default {{ $tone['focus'] }}"
                        :class="dayClass(cell)" x-text="cell.day"></button>
                </div>
            </template>
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 border-t border-slate-100 pt-2">
            <p class="min-w-0 truncate text-xs text-slate-600">
                <span x-show="value" x-text="label(value || today)" class="font-semibold text-slate-800"></span>
                <span x-show="!value" class="text-slate-500">Belum dipilih</span>
            </p>
            {{-- invisible (bukan x-show) agar tinggi footer tidak berubah. --}}
            <button type="button" @click="pickToday()" :class="(disabled || value === today || isOff(today)) && 'invisible'"
                class="shrink-0 rounded px-2 py-1 text-xs font-semibold transition {{ $tone['link'] }}">
                Hari ini
            </button>
        </div>
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
