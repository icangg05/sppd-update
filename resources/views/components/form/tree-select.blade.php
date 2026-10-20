@props([
    'name' => null,
    'label' => null,
    // Pohon: [['value' => ..., 'label' => ..., 'meta' => ?string, 'children' => [...]], ...]
    'nodes' => [],
    'required' => false,
    'hint' => null,
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Belum ada yang dipilih.',
    'wrapperClass' => '',
])

@php
    // Pemilih berjenjang: tiap tingkat menjorok seperti daftar bersarang (ala SortableJS),
    // klik baris untuk memilih. Binding wire:model di-entangle seperti searchable-select.
    $model = $attributes->wire('model');
    $hasWire = is_string($model->value()) && $model->value() !== '';
    $key = $name ?? ($model->value() ?? null);
    $initialValue = $hasWire ? null : old($name, $attributes->get('value'));

    // Ratakan pohon (pre-order) + depth & parent: Alpine cukup merender satu x-for
    // dan menyembunyikan baris yang induknya tertutup.
    $rows = [];
    $flatten = function (array $nodes, int $depth, ?string $parent) use (&$flatten, &$rows) {
        foreach ($nodes as $node) {
            $value = (string) $node['value'];
            $children = $node['children'] ?? [];
            $rows[] = [
                'value' => $value,
                'label' => (string) $node['label'],
                'meta' => $node['meta'] ?? null,
                'parent' => $parent,
                'depth' => $depth,
                'count' => count($children),
            ];
            $flatten($children, $depth + 1, $value);
        }
    };
    $flatten($nodes, 0, null);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    {{-- wire:ignore: baris dirender Alpine (x-for); nilai tetap sinkron lewat entangle. --}}
    <div wire:ignore
        x-data="{
            value: @if ($hasWire) @entangle($model) @else @js((string) $initialValue) @endif,
            rows: @js($rows),
            byValue: {},
            open: {},
            q: '',
            matches: null,
            // instant: tanpa animasi (pencarian & muat awal); anim: arah + tingkat dasar buka/tutup terakhir.
            instant: true,
            anim: null,
            init() {
                this.byValue = Object.fromEntries(this.rows.map(r => [r.value, r]));
                // Hanya satu induk (mis. admin OPD): langsung terbuka.
                const roots = this.rows.filter(r => r.parent === null);
                if (roots.length === 1) this.open[roots[0].value] = true;
                this.revealSelected();
                this.$watch('q', () => this.filter());
            },
            ancestors(v) {
                const out = [];
                let r = this.byValue[String(v ?? '')];
                while (r && r.parent !== null) {
                    out.push(r.parent);
                    r = this.byValue[r.parent];
                }
                return out;
            },
            get path() {
                const r = this.byValue[String(this.value ?? '')];
                return r ? [...this.ancestors(r.value).reverse(), r.value].map(v => this.byValue[v].label) : [];
            },
            isSelected(row) { return String(this.value ?? '') === row.value; },
            onPath(row) { return this.ancestors(this.value).includes(row.value); },
            isOpen(row) { return this.matches ? true : !!this.open[row.value]; },
            visible(row) {
                if (this.matches) return this.matches.has(row.value);
                return this.ancestors(row.value).every(v => this.open[v]);
            },
            // Efek tangga: saat dibuka tingkat dangkal turun lebih dulu; saat ditutup
            // tingkat terdalam terlipat lebih dulu. Dicatat sebelum state open berubah.
            animate(opening, base, within) {
                this.instant = false;
                const depths = this.rows.filter(r => r.depth > base && within(r) && this.visible(r)).map(r => r.depth);
                this.anim = { opening, base, deepest: depths.length ? Math.max(...depths) : base };
            },
            delay(row) {
                if (this.instant || !this.anim) return 0;
                return this.anim.opening
                    ? Math.max(0, row.depth - this.anim.base - 1) * 60
                    : Math.max(0, this.anim.deepest - row.depth) * 40;
            },
            toggle(row) {
                const opening = !this.open[row.value];
                this.animate(opening, row.depth, r => this.ancestors(r.value).includes(row.value));
                this.open[row.value] = opening;
            },
            expandAll(state) {
                this.animate(state, 0, () => true);
                this.rows.forEach(r => { if (r.count) this.open[r.value] = state; });
            },
            choose(row) { this.value = row.value; },
            filter() {
                this.instant = true;
                const s = this.q.trim().toLowerCase();
                if (!s) { this.matches = null; this.revealSelected(); return; }
                const set = new Set();
                this.rows.forEach(r => {
                    if (r.label.toLowerCase().includes(s) || (r.meta || '').toLowerCase().includes(s)) {
                        set.add(r.value);
                        this.ancestors(r.value).forEach(a => set.add(a));
                    }
                });
                this.matches = set;
            },
            // Buka cabang menuju unit terpilih lalu gulir daftar (bukan halaman) ke sana.
            revealSelected() {
                this.ancestors(this.value).forEach(v => this.open[v] = true);
                this.$nextTick(() => {
                    const el = this.$refs.list?.querySelector('[data-selected]');
                    if (el) this.$refs.list.scrollTop = el.offsetTop - this.$refs.list.clientHeight / 2;
                });
            },
        }"
        class="space-y-2">

        <div class="flex flex-wrap items-center gap-2">
            {{-- Enter jangan men-submit form induk. --}}
            <x-form.input x-model="q" x-on:keydown.enter.prevent="" :id="($key ?? 'tree') . '-search'"
                icon="fa-solid fa-magnifying-glass" :placeholder="$searchPlaceholder" wrapperClass="min-w-48 flex-1" />
            <div class="flex gap-2">
                <x-ui.button variant="secondary" size="sm" @click="expandAll(true)">
                    <x-slot:icon><i class="fa-solid fa-angles-down text-xs text-slate-500"></i></x-slot:icon>
                    Buka semua
                </x-ui.button>
                <x-ui.button variant="secondary" size="sm" @click="expandAll(false)">
                    <x-slot:icon><i class="fa-solid fa-angles-up text-xs text-slate-500"></i></x-slot:icon>
                    Tutup semua
                </x-ui.button>
            </div>
        </div>

        {{-- Jalur unit terpilih: induk › sub-unit › unit --}}
        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs">
            <span class="font-semibold text-slate-500">Terpilih:</span>
            <template x-for="(crumb, i) in path" :key="i">
                <span class="inline-flex items-center gap-1.5">
                    <i x-show="i > 0" class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                    <span x-text="crumb"
                        :class="i === path.length - 1 ? 'font-semibold text-primary-700' : 'text-slate-600'"></span>
                </span>
            </template>
            <span x-show="!path.length" class="text-slate-500">{{ $emptyText }}</span>
        </div>

        <div x-ref="list"
            class="relative max-h-80 overflow-y-auto rounded border border-slate-200 bg-slate-50 p-2 pb-1">
            <template x-for="row in rows" :key="row.value">
                {{-- x-collapse menganimasikan tinggi baris; jarak antarbaris lewat pb-1 di dalam
                     (bukan gap) agar ikut terlipat mulus. :style wajib objek — string akan
                     menimpa display/height yang dipasang x-show & x-collapse. --}}
                <div x-show="visible(row)" x-collapse.duration.200ms
                    :style="{ transitionDelay: delay(row) + 'ms' }"
                    :class="instant && 'duration-0!'"
                    class="motion-reduce:duration-0! motion-reduce:delay-0!"
                    :data-selected="isSelected(row) ? 'true' : null">
                    <div class="relative pb-1" :style="{ paddingLeft: row.depth * 20 + 'px' }">
                        {{-- Garis penuntun per tingkat leluhur (menyambung ke baris induk). --}}
                        <template x-for="d in row.depth" :key="d">
                            <span class="pointer-events-none absolute -top-1 bottom-0 w-px bg-slate-300"
                                :style="`left: ${(d - 1) * 20 + 13}px`"></span>
                        </template>

                        <div class="flex min-h-10 items-stretch rounded border transition-colors"
                            :class="isSelected(row)
                                ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500'
                                : (onPath(row) ? 'border-primary-200 bg-white' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50')">
                            <template x-if="row.count">
                                <button type="button" @click="toggle(row)" :aria-expanded="isOpen(row)"
                                    :aria-label="(isOpen(row) ? 'Tutup ' : 'Buka ') + row.label"
                                    class="flex w-7 shrink-0 items-center justify-center rounded-l text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
                                    <i class="fa-solid fa-chevron-right text-[10px] transition-transform"
                                        :class="isOpen(row) && 'rotate-90'"></i>
                                </button>
                            </template>
                            <template x-if="!row.count">
                                <span class="flex w-7 shrink-0 items-center justify-center">
                                    <i class="fa-solid fa-circle text-[4px] text-slate-300"></i>
                                </span>
                            </template>

                            <button type="button" @click="choose(row)" :aria-pressed="isSelected(row)"
                                class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-r py-2 pr-3 text-left text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
                                <span x-text="row.label" class="min-w-0"
                                    :class="isSelected(row) ? 'font-semibold text-primary-800' : (onPath(row) ? 'font-medium text-slate-800' : 'text-slate-700')"></span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <span x-show="row.meta" class="hidden text-[11px] text-slate-500 sm:inline"
                                        x-text="row.count ? `${row.meta} · ${row.count} sub-unit` : row.meta"></span>
                                    <i x-show="isSelected(row)" class="fa-solid fa-circle-check text-primary-600"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <p x-show="matches && matches.size === 0" class="px-2 py-6 text-center text-xs text-slate-500">
                Tidak ada hasil yang cocok.
            </p>
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
