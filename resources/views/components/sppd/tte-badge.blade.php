@props([
    // Status tanda tangan: signed | processing | rejected | null (belum diproses).
    'status' => null,
])

{{-- Badge status TTE dokumen, dipakai di halaman detail SPPD, Kelola SPT, dan Kelola SPPD. --}}
@if ($status === 'signed')
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700']) }}>
        <i class="fa-solid fa-check text-[10px]"></i> Sudah TTE
    </span>
@elseif ($status === 'processing')
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800']) }}>
        <i class="fa-solid fa-spinner animate-spin text-[10px] motion-reduce:animate-none"></i> Proses
    </span>
@elseif ($status === 'rejected')
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700']) }}>
        <i class="fa-solid fa-xmark text-[10px]"></i> Gagal TTE
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600']) }}>Belum TTE</span>
@endif
