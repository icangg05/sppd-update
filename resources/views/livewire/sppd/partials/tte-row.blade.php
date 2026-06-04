<div class="py-2 flex items-center justify-between gap-3 text-xs">
    <div class="min-w-0 flex-1">
        <span class="font-bold text-slate-700 block truncate text-[11px]">{{ $label }}</span>
        <span class="text-[10px] text-slate-500 block truncate mt-0.5">{{ $name }}</span>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        @if ($sig && $sig->status->value === 'signed')
            <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Sudah TTE</span>
            <span class="text-[9px] font-mono text-slate-400"><i class="fa-regular fa-clock"></i> {{ $sig->signed_at?->translatedFormat('d/m H:i') }}</span>
        @elseif ($sig && $sig->status->value === 'processing')
            <span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 animate-pulse">
                <i class="fa-solid fa-spinner animate-spin text-[9px]"></i> Proses
            </span>
        @elseif ($sig && $sig->status->value === 'rejected')
            <span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Gagal TTE</span>
        @else
            <span class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Belum TTE</span>
        @endif
    </div>
</div>
