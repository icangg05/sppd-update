<div class="p-1 space-y-4">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-sitemap pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-network-wired text-[9px]"></i>
          {{ $isSuperAdmin ? 'Struktur Instansi' : 'Unit Kerja' }}
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ count($rows) }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">
          {{ $isSuperAdmin ? 'Data Instansi' : 'Manajemen Unit Kerja' }}
        </h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ $isSuperAdmin ? 'Kelola OPD, kecamatan, kelurahan, dan unit kerja induk' : 'Kelola struktur Bidang & Seksi di lingkup ' . auth()->user()->department?->name }}
        </p>
      </div>

      <x-ui.button href="{{ route('master.departments.create') }}" variant="primary" class="shrink-0 font-bold">
        <x-slot name="icon"><i class="fa-solid fa-plus text-xs"></i></x-slot>
        {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Struktur' }}
      </x-ui.button>
    </div>
  </div>

  {{-- Filter — disaring langsung di pohon (klien), tanpa menunggu server. --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-4">
    <div class="flex flex-col gap-3 sm:flex-row">
      <x-form.input wire:model="search" icon="fa-solid fa-magnifying-glass" wrapperClass="flex-1 w-full"
        placeholder="Cari nama unit kerja atau kode urusan..." />

      @if($isSuperAdmin)
        @php
          $typeOptions = collect($types)
            ->map(fn($t) => ['value' => $t->value, 'label' => $t->label()])
            ->prepend(['value' => '', 'label' => 'Semua Tipe'])
            ->all();
        @endphp
        <div class="w-full sm:w-44">
          <x-form.searchable-select wire:model="type" name="type" :options="$typeOptions"
            placeholder="Semua Tipe" searchPlaceholder="Cari tipe..." />
        </div>
      @endif

      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" x-bind:disabled="!$wire.search && !$wire.type">
          <x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
          Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Pohon struktur: dirender Alpine (wire:ignore); data baru datang lewat event
       department-tree-updated (setelah simpan / hapus).
       Susunan diatur seperti sortable tree: seret naik/turun = urutan, geser kanan/kiri
       = masuk/keluar sub-unit. Perubahan ditampung dulu, baru tersimpan lewat tombol Simpan.
       Tanpa .dash-enter: transform animasinya menjebak elemen fixed (salinan yang diseret). --}}
  <div wire:ignore
    x-data="{
      rows: @js($rows),
      serverRows: [],
      byId: {},
      anc: {},
      open: {},
      matches: null,
      hits: null,
      instant: true,
      anim: null,
      flashIds: {},
      canRoot: @js($isSuperAdmin),
      storageKey: 'sppd:dept-tree-open',
      // Jarak menjorok per tingkat: lebih rapat di layar sempit agar nama unit tetap lega.
      indent: 20,
      // Susunan terakhir yang tersimpan (id → induk & urutan) dan perubahan yang belum disimpan.
      baseline: {},
      touched: {},
      changed: {},
      pending: { moved: 0, reordered: 0, total: 0 },
      saving: false,
      drag: { pending: false, active: false, row: null, x: 0, y: 0, startX: 0, startY: 0, grabX: 0, grabY: 0, width: 0, baseDepth: 0, key: '', parentName: '', snapshot: null, dwellId: null, dwellTimer: null, cleanup: null },
      init() {
        const wide = window.matchMedia('(min-width: 640px)');
        this.indent = wide.matches ? 20 : 14;
        wide.addEventListener('change', e => this.indent = e.matches ? 20 : 14);
        this.setRows(this.rows);
        this.loadOpen();
        // Admin OPD (satu induk): langsung terbuka bila belum ada yang tersimpan.
        const roots = this.rows.filter(r => r.parent === null);
        if (roots.length === 1 && !Object.keys(this.open).length) this.open[roots[0].id] = true;
        this.filter();
        this.$watch('$wire.search', () => this.filter());
        this.$watch('$wire.type', () => this.filter());
        // Cegah perubahan yang belum disimpan hilang: muat ulang/tutup tab & navigasi SPA.
        this.onUnload = e => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } };
        this.onNavigate = e => { if (this.dirty && !confirm('Perubahan struktur belum disimpan. Tinggalkan halaman ini?')) e.preventDefault(); };
        window.addEventListener('beforeunload', this.onUnload);
        document.addEventListener('livewire:navigate', this.onNavigate);
      },
      destroy() {
        window.removeEventListener('beforeunload', this.onUnload);
        document.removeEventListener('livewire:navigate', this.onNavigate);
      },
      get dirty() { return this.pending.total > 0; },
      // Hitung ulang turunan struktur: leluhur, kedalaman, dan jumlah sub-unit.
      index() {
        this.byId = Object.fromEntries(this.rows.map(r => [r.id, r]));
        this.anc = {};
        const kids = {};
        this.rows.forEach(r => {
          const out = [];
          let p = r.parent;
          while (p !== null && this.byId[p]) { out.push(p); p = this.byId[p].parent; }
          this.anc[r.id] = out;
          if (r.depth !== out.length) r.depth = out.length;
          if (r.parent !== null) kids[r.parent] = (kids[r.parent] || 0) + 1;
        });
        this.rows.forEach(r => { if (r.children !== (kids[r.id] || 0)) r.children = kids[r.id] || 0; });
      },
      ancestors(id) { return this.anc[id] || []; },
      structure() {
        const out = {}, n = {};
        this.rows.forEach(r => {
          const k = r.parent ?? 'root';
          n[k] = (n[k] ?? -1) + 1;
          out[r.id] = { parent: r.parent, order: n[k] };
        });
        return out;
      },
      recount() {
        const now = this.structure();
        const changed = {};
        let moved = 0, reordered = 0;
        for (const id in now) {
          const b = this.baseline[id];
          if (!b) continue;
          if (b.parent !== now[id].parent) { moved++; changed[id] = true; }
          else if (b.order !== now[id].order) { reordered++; if (this.touched[id]) changed[id] = true; }
        }
        this.changed = changed;
        this.pending = { moved, reordered, total: moved + reordered };
      },
      summary() {
        const parts = [];
        if (this.pending.moved) parts.push(`${this.pending.moved} unit pindah induk`);
        if (this.pending.reordered) parts.push('urutan berubah');
        return 'Perubahan belum disimpan: ' + parts.join(' · ');
      },
      loadOpen() {
        try {
          (JSON.parse(localStorage.getItem(this.storageKey) || '[]') || []).forEach(id => { if (this.byId[id]) this.open[id] = true; });
        } catch (e) {}
      },
      saveOpen() {
        try { localStorage.setItem(this.storageKey, JSON.stringify(Object.keys(this.open).filter(k => this.open[k]).map(Number))); } catch (e) {}
      },
      isOpen(row) { return this.matches ? true : !!this.open[row.id]; },
      visible(row) {
        // Sub-unit dari unit yang sedang diseret ikut terlipat selama diseret.
        if (this.drag.active && this.ancestors(row.id).includes(this.drag.row.id)) return false;
        if (this.matches) return this.matches.has(row.id);
        return this.ancestors(row.id).every(a => this.open[a]);
      },
      // Efek tangga seperti di form pegawai: saat dibuka tingkat dangkal turun lebih dulu,
      // saat ditutup tingkat terdalam terlipat lebih dulu.
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
        const opening = !this.open[row.id];
        this.animate(opening, row.depth, r => this.ancestors(r.id).includes(row.id));
        this.open[row.id] = opening;
        this.saveOpen();
      },
      expandAll(state) {
        this.animate(state, 0, () => true);
        this.rows.forEach(r => { if (r.children) this.open[r.id] = state; });
        this.saveOpen();
      },
      filter(instant = true) {
        if (instant) this.instant = true;
        const q = String(this.$wire.search ?? '').trim().toLowerCase();
        const t = String(this.$wire.type ?? '');
        if (!q && !t) { this.matches = null; this.hits = null; return; }
        const hits = new Set(), vis = new Set();
        this.rows.forEach(r => {
          const okQ = !q || r.name.toLowerCase().includes(q) || (r.code || '').toLowerCase().includes(q) || (r.typeLabel || '').toLowerCase().includes(q);
          if (okQ && (!t || r.type === t)) {
            hits.add(r.id);
            vis.add(r.id);
            this.ancestors(r.id).forEach(a => vis.add(a));
          }
        });
        this.hits = hits;
        this.matches = vis;
      },
      // Data dari server menjadi titik awal baru (perubahan lokal dianggap tersimpan).
      setRows(rows, focus = null) {
        this.serverRows = rows;
        this.rows = rows.map(r => ({ ...r }));
        this.index();
        this.baseline = this.structure();
        this.touched = {};
        this.recount();
        this.filter(false);
        if (focus && this.byId[focus]) this.flashRows([focus], true);
      },
      flashRows(ids, reveal = false) {
        // Dua tahap: hijau ditahan sebentar ('on'), lalu memudar perlahan ke warna normal ('fade').
        ids.forEach(id => this.flashIds[id] = 'on');
        setTimeout(() => ids.forEach(id => { if (this.flashIds[id] === 'on') this.flashIds[id] = 'fade'; }), 1200);
        setTimeout(() => ids.forEach(id => { if (this.flashIds[id] === 'fade') delete this.flashIds[id]; }), 2300);
        if (!reveal) return;
        this.instant = false;
        this.anim = { opening: true, base: 0, deepest: 0 };
        this.ancestors(ids[0]).forEach(a => this.open[a] = true);
        this.saveOpen();
        setTimeout(() => {
          const el = this.$refs.list.querySelector(`[data-row-id='${ids[0]}']`);
          const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          el?.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
        }, 260);
      },
      resetChanges() {
        this.flip(() => {
          this.rows = this.serverRows.map(r => ({ ...r }));
          this.index();
        });
        this.touched = {};
        this.recount();
        this.filter(false);
      },
      async save() {
        if (!this.dirty || this.saving) return;
        const order = this.structure();
        const nodes = this.rows.map(r => ({ id: r.id, parent: r.parent, order: order[r.id].order }));
        const moved = Object.keys(this.changed).map(Number);
        this.saving = true;
        try {
          if (await this.$wire.saveStructure(nodes)) this.flashRows(moved);
        } finally {
          this.saving = false;
        }
      },
      cardClass(row) {
        if (this.drag.active && row.id === this.drag.row.id) return 'border-dashed border-primary-400 bg-primary-50 [&>*]:opacity-30';
        if (this.flashIds[row.id] === 'on') return 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-400/30';
        // Tahap pudar: kelas normal + transisi panjang agar hijau hilang halus, bukan mendadak.
        const fade = this.flashIds[row.id] === 'fade' ? ' duration-1000 ease-out' : '';
        if (this.changed[row.id]) return 'border-amber-300 bg-amber-50/50' + fade;
        if (this.hits && !this.hits.has(row.id)) return 'border-slate-200 bg-white opacity-60' + fade;
        if (row.isOwn) return 'border-primary-200 bg-primary-50/40' + fade;
        return 'border-slate-200 bg-white hover:border-slate-300' + fade;
      },
      // Animasi FLIP (seperti SortableJS): baris yang bergeser posisi meluncur ke tempat barunya.
      flip(mutate) {
        const list = this.$refs.list;
        const before = {};
        list.querySelectorAll(':scope > [data-row-id]').forEach(el => {
          if (el.offsetParent) before[el.dataset.rowId] = el.getBoundingClientRect().top;
        });
        mutate();
        this.$nextTick(() => {
          list.querySelectorAll(':scope > [data-row-id]').forEach(el => {
            const old = before[el.dataset.rowId];
            if (old === undefined || !el.offsetParent) return;
            el.style.transform = '';
            const dy = old - el.getBoundingClientRect().top;
            if (Math.abs(dy) < 1) return;
            el.style.transition = 'none';
            el.style.transform = `translateY(${dy}px)`;
            el.getBoundingClientRect();
            el.style.transition = 'transform 150ms ease';
            el.style.transform = '';
            clearTimeout(el._flipTimer);
            el._flipTimer = setTimeout(() => { el.style.transition = ''; }, 170);
          });
        });
      },
      // ── Seret & lepas (Pointer Events: mouse + sentuh) ──
      startDrag(e, row) {
        if (!row.canDrag || this.matches || this.saving || (e.pointerType === 'mouse' && e.button !== 0)) return;
        e.preventDefault();
        const rect = e.currentTarget.closest('[data-card]').getBoundingClientRect();
        Object.assign(this.drag, {
          pending: true, active: false, row,
          x: e.clientX, y: e.clientY, startX: e.clientX, startY: e.clientY,
          grabX: e.clientX - rect.left, grabY: e.clientY - rect.top, width: rect.width,
        });
        const move = ev => this.onDragMove(ev);
        const up = () => this.endDrag(true);
        const cancel = () => this.endDrag(false);
        const key = ev => { if (ev.key === 'Escape') this.endDrag(false); };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
        window.addEventListener('pointercancel', cancel);
        window.addEventListener('keydown', key);
        this.drag.cleanup = () => {
          window.removeEventListener('pointermove', move);
          window.removeEventListener('pointerup', up);
          window.removeEventListener('pointercancel', cancel);
          window.removeEventListener('keydown', key);
        };
      },
      onDragMove(ev) {
        const d = this.drag;
        d.x = ev.clientX;
        d.y = ev.clientY;
        if (!d.active) {
          if (Math.hypot(d.x - d.startX, d.y - d.startY) < 5) return;
          d.active = true;
          d.baseDepth = d.row.depth;
          d.snapshot = this.rows.map(r => [r.id, r.parent]);
          d.key = this.placementKey(d.row.parent, this.prevSibling(d.row));
          d.parentName = this.parentLabel(d.row.parent);
          this.instant = false;
          this.anim = null;
          document.body.classList.add('select-none');
          document.body.style.cursor = 'grabbing';
          this.autoScroll();
        }
        this.place();
      },
      prevSibling(row) {
        const i = this.rows.findIndex(r => r.id === row.id);
        for (let j = i - 1; j >= 0; j--) if (this.rows[j].parent === row.parent) return this.rows[j];
        return null;
      },
      placementKey(parent, after) { return `${parent}|${after ? after.id : ''}`; },
      parentLabel(parent) {
        if (parent !== null) return 'Di bawah ' + this.byId[parent].name;
        return 'Instansi induk (tanpa induk)';
      },
      // Inti sortable tree: posisi vertikal pointer menentukan urutan, geseran horizontal
      // dari titik awal menentukan kedalaman (kanan = masuk sub-unit, kiri = keluar).
      place() {
        const d = this.drag, active = d.row, list = this.$refs.list;
        const top = list.getBoundingClientRect().top;
        const vis = this.rows.filter(r => this.visible(r));
        const k = vis.findIndex(r => r.id === active.id);
        if (k < 0) return;
        const elOf = r => list.querySelector(`:scope > [data-row-id='${r.id}']`);
        const mid = r => { const el = elOf(r); return top + el.offsetTop + el.offsetHeight / 2; };

        let target = k;
        while (target > 0 && d.y < mid(vis[target - 1])) target--;
        if (target === k) while (target < vis.length - 1 && d.y > mid(vis[target + 1])) target++;

        const order = vis.filter(r => r.id !== active.id);
        order.splice(target, 0, active);
        const prev = order[target - 1], next = order[target + 1];

        // Unit tertutup yang punya sub-unit baru bisa dimasuki setelah ditahan sebentar (terbuka sendiri).
        const prevCollapsed = prev && prev.children && !this.open[prev.id];
        this.dwell(prevCollapsed ? prev : null);

        // Satu tingkat per ±40px geseran horizontal (cukup lebar untuk jari di HP).
        const projected = d.baseDepth + Math.round((d.x - d.startX) / 40);
        const max = prev ? prev.depth + (prevCollapsed ? 0 : 1) : 0;
        const min = Math.max(next ? next.depth : 0, this.canRoot ? 0 : 1);
        if (min > max) return;
        const depth = Math.min(max, Math.max(min, projected));

        let parent = null, after = null;
        for (let i = target - 1; i >= 0; i--) {
          if (order[i].depth === depth) { after = order[i]; parent = order[i].parent; break; }
          if (order[i].depth < depth) { parent = order[i].id; break; }
        }

        const key = this.placementKey(parent, after);
        if (key === d.key) return;
        d.key = key;
        d.parentName = this.parentLabel(parent);

        this.flip(() => {
          const inBlock = r => r.id === active.id || this.ancestors(r.id).includes(active.id);
          const block = this.rows.filter(inBlock);
          const rest = this.rows.filter(r => !inBlock(r));
          let at = 0;
          if (after) {
            at = rest.findIndex(r => r.id === after.id) + 1;
            while (at < rest.length && this.ancestors(rest[at].id).includes(after.id)) at++;
          } else if (parent !== null) {
            at = rest.findIndex(r => r.id === parent) + 1;
          }
          active.parent = parent;
          rest.splice(at, 0, ...block);
          this.rows = rest;
          this.index();
          // Masuk ke unit tanpa sub-unit: buka agar baris yang diseret tetap terlihat.
          if (parent !== null && !this.open[parent]) this.open[parent] = true;
        });
      },
      dwell(row) {
        const id = row ? row.id : null;
        if (id === this.drag.dwellId) return;
        clearTimeout(this.drag.dwellTimer);
        this.drag.dwellId = id;
        if (!id) return;
        this.drag.dwellTimer = setTimeout(() => {
          if (!this.drag.active || this.drag.dwellId !== id) return;
          this.animate(true, row.depth, r => this.ancestors(r.id).includes(id));
          this.open[id] = true;
          this.saveOpen();
          this.drag.dwellId = null;
          setTimeout(() => this.drag.active && this.place(), 220);
        }, 700);
      },
      autoScroll() {
        const step = () => {
          if (!this.drag.active) return;
          const y = this.drag.y, top = 140, bottom = window.innerHeight - 60;
          const dy = y < top ? -Math.min(18, Math.ceil((top - y) / 5)) : (y > bottom ? Math.min(18, Math.ceil((y - bottom) / 5)) : 0);
          if (dy) { window.scrollBy(0, dy); this.place(); }
          requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      },
      endDrag(drop) {
        const d = this.drag;
        d.cleanup?.();
        clearTimeout(d.dwellTimer);
        if (d.active) {
          if (!drop) {
            // Esc / dibatalkan sistem: kembalikan ke posisi sebelum diseret.
            this.flip(() => {
              this.rows = d.snapshot.map(([id, parent]) => { const r = this.byId[id]; r.parent = parent; return r; });
              this.index();
            });
          } else {
            this.touched[d.row.id] = true;
          }
          this.recount();
        }
        Object.assign(d, { pending: false, active: false, row: null, snapshot: null, dwellId: null, cleanup: null });
        document.body.classList.remove('select-none');
        document.body.style.cursor = '';
      },
    }"
    x-on:department-tree-updated.window="setRows($event.detail.rows, $event.detail.focus)"
    class="rounded border border-slate-200 bg-white shadow-sm">

    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-xs leading-relaxed text-slate-500">
        <template x-if="!matches">
          <span>
            <i class="fa-solid fa-grip-vertical mr-1 text-slate-400"></i>
            Seret naik/turun untuk mengubah urutan; geser ke kanan untuk memasukkan ke sub-unit, ke kiri untuk mengeluarkan.
            Perubahan tersimpan setelah menekan <span class="font-semibold text-slate-700">Simpan perubahan</span>.
          </span>
        </template>
        <template x-if="matches">
          <span>
            <span class="font-semibold text-slate-700" x-text="`${hits?.size ?? 0} unit cocok.`"></span>
            Kosongkan pencarian & filter untuk menyusun struktur.
          </span>
        </template>
      </p>
      <div class="flex shrink-0 gap-2">
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

    <div x-ref="list" class="relative p-3 pb-1.5">
      <template x-for="row in rows" :key="row.id">
        {{-- x-collapse menganimasikan tinggi baris; jarak antarbaris lewat pb-1.5 di dalam
             (bukan gap) agar ikut terlipat. :style wajib objek agar tidak menimpa display/height. --}}
        <div x-show="visible(row)" x-collapse.duration.200ms
          :style="{ transitionDelay: delay(row) + 'ms' }"
          :class="instant && 'duration-0!'"
          class="motion-reduce:duration-0! motion-reduce:delay-0!"
          :data-row-id="row.id">
          <div class="relative pb-1.5" :style="{ paddingLeft: row.depth * indent + 'px' }">
            {{-- Garis penuntun per tingkat leluhur. --}}
            <template x-for="d in row.depth" :key="d">
              <span class="pointer-events-none absolute -top-1.5 bottom-0 w-px bg-slate-300"
                :style="{ left: (d - 1) * indent + indent / 2 + 'px' }"></span>
            </template>

            <div data-card class="flex flex-wrap items-center gap-x-1 gap-y-2 rounded border py-1.5 pl-1 pr-2 transition sm:flex-nowrap"
              :class="cardClass(row)">
              {{-- Pegangan seret --}}
              <button type="button" x-show="row.canDrag && !matches" @pointerdown="startDrag($event, row)"
                :aria-label="'Seret untuk memindahkan ' + row.name" title="Seret untuk memindahkan"
                class="flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
                <i class="fa-solid fa-grip-vertical text-xs"></i>
              </button>
              <span x-show="!row.canDrag || matches" class="w-6 shrink-0"></span>

              {{-- Buka/tutup sub-unit --}}
              <button type="button" x-show="row.children" @click="toggle(row)" :aria-expanded="isOpen(row)"
                :aria-label="(isOpen(row) ? 'Tutup ' : 'Buka ') + row.name"
                class="flex h-8 w-6 shrink-0 items-center justify-center rounded text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
                <i class="fa-solid fa-chevron-right text-[10px] transition-transform" :class="isOpen(row) && 'rotate-90'"></i>
              </button>
              <span x-show="!row.children" class="flex h-8 w-6 shrink-0 items-center justify-center">
                <i class="fa-solid fa-circle text-[4px] text-slate-300"></i>
              </span>

              {{-- Nama & keterangan --}}
              <div class="min-w-0 flex-1 py-0.5">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <span class="text-sm font-semibold" :class="row.isOwn ? 'text-primary-700' : 'text-slate-800'"
                    x-text="row.name"></span>
                  <x-ui.badge color="cyan" x-show="row.isOwn">Instansi Anda</x-ui.badge>
                  <x-ui.badge color="amber" x-show="changed[row.id]">Belum disimpan</x-ui.badge>
                  <x-ui.badge color="amber" x-show="!row.hasKop" class="gap-1"
                    title="Unit ini belum memiliki kop surat, dan tidak mewarisi kop dari instansi induk.">
                    <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Belum ada kop
                  </x-ui.badge>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-slate-500">
                  <x-ui.badge bg="" text="" class="border" x-show="row.typeLabel" x-bind:class="row.typeBadge"
                    x-text="row.typeLabel" />
                  <span x-show="row.code" class="font-mono text-slate-600" x-text="row.code"></span>
                  <span><i class="fa-solid fa-users mr-1 text-slate-400"></i><span class="font-mono" x-text="row.users"></span> pegawai</span>
                  <span x-show="row.children"><i class="fa-solid fa-sitemap mr-1 text-slate-400"></i><span class="font-mono" x-text="row.children"></span> sub-unit</span>
                </div>
              </div>

              {{-- Aksi. Selama ada susunan yang belum disimpan, hapus dikunci agar tidak bertabrakan. --}}
              <div class="ml-auto flex shrink-0 items-center gap-1.5">
                <a :href="row.showUrl" wire:navigate title="Detail"
                  class="flex size-8 shrink-0 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition-colors hover:border-primary-200 hover:bg-primary-50 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
                  <i class="fa-solid fa-eye text-xs"></i>
                </a>
                <a :href="row.editUrl" wire:navigate title="Edit"
                  class="flex size-8 shrink-0 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition-colors hover:border-amber-200 hover:bg-amber-50 hover:text-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
                  <i class="fa-solid fa-pen-to-square text-xs"></i>
                </a>
                <button type="button" x-show="row.canDelete" @click="$wire.confirmDelete(row.id)" :disabled="dirty"
                  :title="dirty ? 'Simpan atau batalkan perubahan struktur dulu' : 'Hapus'"
                  class="flex size-8 shrink-0 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition-colors enabled:hover:border-rose-200 enabled:hover:bg-rose-50 enabled:hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40 disabled:cursor-not-allowed disabled:opacity-50">
                  <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      {{-- Kosong --}}
      <div x-show="!rows.length || (hits && !hits.size)" x-cloak class="px-4 py-14">
        <div class="mx-auto flex max-w-sm flex-col items-center text-center">
          <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
            <i class="fa-solid fa-folder-tree text-xl"></i>
          </div>
          <template x-if="rows.length">
            <div>
              <p class="mt-4 text-sm font-semibold text-slate-700">Tidak ada unit kerja yang cocok</p>
              <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci atau hapus filter tipe yang aktif.</p>
              <x-ui.button type="button" variant="secondary" size="sm" class="mt-4" @click="$wire.resetFilters()">
                <x-slot:icon><i class="fa-solid fa-rotate-right text-xs"></i></x-slot:icon>
                Reset filter
              </x-ui.button>
            </div>
          </template>
          <template x-if="!rows.length">
            <div>
              <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada data unit kerja</p>
              <p class="mt-1 text-xs text-slate-500">
                Tambahkan {{ $isSuperAdmin ? 'instansi/OPD' : 'struktur bidang atau seksi' }} untuk mulai menyusun struktur.
              </p>
              <x-ui.button href="{{ route('master.departments.create') }}" variant="primary" size="sm" class="mt-4">
                <x-slot:icon><i class="fa-solid fa-plus text-xs"></i></x-slot:icon>
                {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Struktur' }}
              </x-ui.button>
            </div>
          </template>
        </div>
      </div>
    </div>

    {{-- Bar simpan: muncul hanya bila ada susunan yang belum disimpan; menempel di bawah layar. --}}
    <div x-show="dirty" x-cloak x-transition.opacity
      class="sticky bottom-0 z-20 rounded-b border-t border-amber-200 bg-amber-50 px-4 py-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2 text-xs text-amber-900">
          <i class="fa-solid fa-circle-exclamation mt-0.5 text-amber-600"></i>
          <div>
            <p class="font-semibold" x-text="summary()"></p>
            <p class="text-amber-800">Penyetuju SPPD dan cakupan data pegawai mengikuti struktur baru setelah disimpan.</p>
          </div>
        </div>
        <div class="flex shrink-0 gap-2">
          <x-ui.button variant="secondary" size="sm" @click="resetChanges()" x-bind:disabled="saving">
            <x-slot:icon><i class="fa-solid fa-rotate-left text-xs text-slate-500"></i></x-slot:icon>
            Batalkan
          </x-ui.button>
          <x-ui.button variant="primary" size="sm" @click="save()" x-bind:disabled="saving">
            <x-slot:icon><i class="fa-solid text-xs" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i></x-slot:icon>
            <span x-text="saving ? 'Menyimpan…' : 'Simpan perubahan'"></span>
          </x-ui.button>
        </div>
      </div>
    </div>

    {{-- Salinan baris yang mengikuti pointer selama diseret (teleport: lepas dari stacking context). --}}
    <template x-teleport="body">
      <div x-show="drag.active" x-cloak class="pointer-events-none fixed z-70"
        :style="{ left: drag.x - drag.grabX + 'px', top: drag.y - drag.grabY + 'px', width: drag.width + 'px' }">
        <div class="flex items-center gap-x-1 rounded border border-primary-300 bg-white py-1.5 pl-1 pr-3 shadow-xl ring-1 ring-primary-500/20">
          <span class="flex h-8 w-6 shrink-0 items-center justify-center text-primary-500">
            <i class="fa-solid fa-grip-vertical text-xs"></i>
          </span>
          <div class="min-w-0 flex-1 py-0.5 pl-1">
            <p class="truncate text-sm font-semibold text-slate-800" x-text="drag.row?.name"></p>
            <p class="mt-0.5 truncate text-[11px] font-semibold text-primary-700">
              <i class="fa-solid fa-arrow-turn-up mr-1 rotate-90"></i>
              <span x-text="drag.parentName"></span>
              <span x-show="drag.row?.children" class="font-normal text-slate-500"
                x-text="`· ${drag.row?.children} sub-unit ikut`"></span>
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>


  {{-- Modal Konfirmasi Hapus — hanya bisa ditutup lewat tombol (tidak closeable) --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Unit Kerja"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
    <div class="space-y-4">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus
        <span class="font-bold text-slate-800">{{ $deletingName ?? 'unit kerja ini' }}</span>?
        Data yang sudah dihapus tidak dapat dikembalikan.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">
          Tutup
        </x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="delete">
          <span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
          <span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

</div>
