@props([
    // Ditandatangani simulasi (TTE Testing), bukan BSrE.
    'testing' => false,
])

{{-- Penanda kecil di samping nama dokumen yang sudah ditandatangani elektronik. --}}
@if ($testing)
    <span {{ $attributes->merge(['class' => 'ml-1 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white']) }}>TTE Testing</span>
@else
    <span {{ $attributes->merge(['class' => 'ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800']) }}>TTE</span>
@endif
