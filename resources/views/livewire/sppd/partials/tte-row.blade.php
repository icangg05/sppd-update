{{-- Satu baris status TTE. Nama ditulis utuh (boleh turun baris), status di kanan jenis dokumen,
     waktu tanda tangan di bawah nama, sehingga kolom samping yang sempit tidak memotong nama. --}}
<div class="px-5 py-3">
    <div class="flex items-center justify-between gap-3">
        <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
        <x-sppd.tte-badge :status="$sig?->status->value" :testing="(bool) $sig?->isSimulated()" />
    </div>
    <p class="mt-0.5 break-words text-sm font-semibold text-slate-800">{{ $name }}</p>
    {{-- Belum TTE: tampilkan kerangka kosong selebar format aslinya agar baris tetap rata. --}}
    @if ($sig?->status->value === 'signed' && $sig->signed_at)
        <p class="mt-1 font-mono text-[11px] text-slate-500">
            <i class="fa-regular fa-clock mr-0.5"></i> {{ $sig->signed_at->translatedFormat('d M Y H:i') }} WITA
        </p>
    @else
        <p class="mt-1 font-mono text-[11px] text-slate-400">
            <i class="fa-regular fa-clock mr-0.5"></i> __ ___ ____ __:__ WITA
        </p>
    @endif
</div>
