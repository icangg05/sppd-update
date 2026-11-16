@props([
    // Flag tahap alur: pejabatnya menandatangani SPT / SPPD (signs_spt / signs_sppd).
    'spt' => false,
    'sppd' => false,
])

@php
    $documents = array_keys(array_filter(['SPT' => (bool) $spt, 'SPPD' => (bool) $sppd]));
@endphp

{{-- Penanda penanda tangan dokumen pada tahap alur (Buat SPPD, isian detail, detail SPPD). --}}
@if ($documents)
    <x-ui.badge color="indigo" size="xs" :class="trim('font-semibold ' . $attributes->get('class'))"
        :title="'Menandatangani ' . implode(' dan ', $documents) . ' secara elektronik'">
        <i class="fa-solid fa-signature text-[8px]" aria-hidden="true"></i>
        TTE {{ implode(' & ', $documents) }}
    </x-ui.badge>
@endif
