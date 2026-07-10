@props([
    'name' => null,
    'label' => null,
    'options' => [],
    'placeholder' => '— Pilih —',
    'searchPlaceholder' => 'Cari...',
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
    // Ambil property dari wire:model agar bisa di-entangle ke Livewire.
    // Catatan: tanpa wire:model, value() mengembalikan false (bukan null), jadi cek tipe string.
    $model = $attributes->wire('model');
    $hasWire = is_string($model->value()) && $model->value() !== '';
    $key = $name ?? ($model->value() ?? null);
    // Untuk form non-Livewire, pakai nilai awal dari old()/value agar tetap tersubmit.
    $initialValue = $hasWire ? null : old($name, $attributes->get('value'));
    // Normalisasi options menjadi list of ['value' => ..., 'label' => ...].
    $normalized = collect($options)->map(function ($opt) {
        if (is_array($opt)) {
            return ['value' => (string) ($opt['value'] ?? ''), 'label' => (string) ($opt['label'] ?? '')];
        }
        return ['value' => (string) $opt, 'label' => (string) $opt];
    })->values()->all();
@endphp

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
            search: '',
            highlighted: 0,
            dropUp: false,
            coords: { top: 0, left: 0, width: 0 },
            value: @if ($hasWire) @entangle($model) @else @js((string) $initialValue) @endif,
            options: @js($normalized),
            get selected() {
                return this.options.find(o => String(o.value) === String(this.value ?? ''));
            },
            get selectedLabel() {
                return this.selected ? this.selected.label : '';
            },
            get filtered() {
                if (!this.search.trim()) return this.options;
                const s = this.search.toLowerCase();
                return this.options.filter(o => o.label.toLowerCase().includes(s));
            },
            position() {
                const r = this.$refs.trigger.getBoundingClientRect();
                const margin = 4;
                const panelH = (this.$refs.panel && this.$refs.panel.offsetHeight) ? this.$refs.panel.offsetHeight : 280;
                const spaceBelow = window.innerHeight - r.bottom;
                const spaceAbove = r.top;
                // Buka ke atas bila ruang bawah kurang & ruang atas lebih lega.
                this.dropUp = spaceBelow < panelH + margin && spaceAbove > spaceBelow;
                const top = this.dropUp
                    ? Math.max(margin, r.top - panelH - margin)
                    : r.bottom + margin;
                this.coords = { top, left: r.left, width: r.width };
            },
            toggle() {
                if ({{ $disabled ? 'true' : 'false' }}) return;
                this.open = !this.open;
                if (this.open) {
                    this.search = '';
                    this.resetHighlight();
                    this.$nextTick(() => {
                        this.position();
                        // Jangan auto-focus di perangkat sentuh (mobile/tablet) agar
                        // keyboard tidak muncul otomatis; user fokus manual saat mengetik.
                        if (this.canAutoFocus()) {
                            this.$refs.search && this.$refs.search.focus();
                        }
                    });
                }
            },
            canAutoFocus() {
                return window.matchMedia && window.matchMedia('(pointer: fine)').matches;
            },
            resetHighlight() {
                // Default sorot ke item pertama (atau item yang sedang terpilih bila ada).
                const i = this.filtered.findIndex(o => String(o.value) === String(this.value ?? ''));
                this.highlighted = i >= 0 ? i : 0;
            },
            move(dir) {
                const len = this.filtered.length;
                if (!len) return;
                let n = this.highlighted + dir;
                if (n < 0) n = len - 1;
                if (n >= len) n = 0;
                this.highlighted = n;
                this.$nextTick(() => this.ensureVisible());
            },
            ensureVisible() {
                const list = this.$refs.list;
                if (!list) return;
                const el = list.querySelector('[data-index=\'' + this.highlighted + '\']');
                if (el) el.scrollIntoView({ block: 'nearest' });
            },
            pick() {
                const item = this.filtered[this.highlighted] ?? this.filtered[0];
                if (item) this.choose(item.value);
            },
            onOutside(e) {
                if (!this.open) return;
                const t = e.target;
                if (this.$refs.trigger.contains(t)) return;
                if (this.$refs.panel && this.$refs.panel.contains(t)) return;
                this.open = false;
            },
            choose(val) {
                this.value = val;
                this.open = false;
                this.search = '';
            },
        }"
        @keydown.escape.window="open = false"
        @click.window="onOutside($event)"
        @scroll.window.capture="if (open) position()"
        @resize.window="if (open) position()"
        class="relative">

        @unless ($hasWire)
            <input type="hidden" @if ($name) name="{{ $name }}" @endif x-bind:value="value">
        @endunless

        <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open"
            @class([
                'flex w-full items-center justify-between gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                'cursor-not-allowed bg-slate-50' => $disabled,
                $class,
            ])>
            <span class="truncate text-left" :class="selectedLabel ? 'text-slate-800' : 'text-slate-500'"
                x-text="selectedLabel || @js($placeholder)"></span>
            <i class="fa-solid fa-chevron-down text-xs text-slate-500 transition-transform"
                :class="open && 'rotate-180'"></i>
        </button>

        <template x-teleport="body">
            <div x-ref="panel" x-show="open" x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :style="`position: fixed; top: ${coords.top}px; left: ${coords.left}px; width: ${coords.width}px; z-index: 9999; transform-origin: ${dropUp ? 'bottom' : 'top'};`"
                class="overflow-hidden rounded border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
                <div class="border-b border-slate-100 p-2">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-[11px] text-slate-500" style="display:flex;"></i>
                        <input x-ref="search" x-model="search" type="text"
                            @input="resetHighlight(); $nextTick(() => position())"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            @keydown.enter.prevent="pick()"
                            @keydown.tab.prevent="move($event.shiftKey ? -1 : 1)"
                            placeholder="{{ $searchPlaceholder }}"
                            class="w-full rounded border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2 text-sm outline-none focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>
                <ul x-ref="list" class="max-h-56 overflow-auto py-1">
                    <template x-for="(opt, index) in filtered" :key="opt.value">
                        <li @click="choose(opt.value)" @mouseenter="highlighted = index" :data-index="index"
                            class="cursor-pointer px-3 py-2 text-sm transition-colors"
                            :class="index === highlighted
                                ? 'bg-primary-100 text-primary-800'
                                : (String(opt.value) === String(value ?? '') ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-700 hover:bg-primary-50')">
                            <span x-text="opt.label"></span>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-3 py-2 text-xs text-slate-500">
                        Tidak ada hasil.
                    </li>
                </ul>
            </div>
        </template>
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
