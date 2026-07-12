{{-- Tombol aksi satu baris SPPD. Dipakai tabel desktop & kartu mobile.
     Param: $sppd, $isApprovalMode, opsional $wrapperClass. --}}
@php
	$allGreen = false;
	if (in_array($sppd->status->value, ['approved', 'completed'])) {
	    $isPastEndDate = today()->greaterThan($sppd->end_date);
	    $isRealized = $sppd->actualExpenses->isNotEmpty() && $sppd->costDetails->isNotEmpty();
	    $isReported =
	        $sppd->report &&
	        $sppd->report->report_date &&
	        $sppd->report->report_file &&
	        $sppd->report->documentation_file;
	    $allGreen = $isPastEndDate && $isRealized && $isReported;
	}
@endphp

<div class="{{ $wrapperClass ?? 'flex flex-col gap-1 w-[115px]' }}">
	<a href="{{ $isApprovalMode ? route('sppd.show', ['sppd' => $sppd, 'from' => 'approval']) : route('sppd.show', $sppd) }}"
		wire:navigate
		class="inline-flex items-center justify-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-2 py-1.5 text-[11px] font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-200 w-full text-center">
		<i class="fa-solid fa-eye text-[10px] text-slate-500"></i>
		<span>Lihat</span>
	</a>

	@if ($sppd->status->value === 'in_progress' && $sppd->revision_note && auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
		<a href="{{ route('sppd.create.details', ['sppd_id' => $sppd->id]) }}" wire:navigate
			class="inline-flex items-center justify-center gap-1.5 rounded border border-amber-300 bg-amber-50 px-2 py-1.5 text-[11px] font-semibold text-amber-700 shadow-2xs transition hover:bg-amber-100 w-full text-center">
			<i class="fa-solid fa-pen-to-square text-[10px] text-amber-600"></i>
			<span>Edit Perbaikan</span>
		</a>
	@endif

	@if (in_array($sppd->status->value, ['approved', 'completed']))
		<a href="{{ route('sppd.next', $sppd) }}" wire:navigate
			class="inline-flex items-center justify-center gap-1.5 rounded bg-primary-600 px-2 py-1.5 text-[11px] font-bold text-white shadow-2xs transition hover:bg-primary-700 w-full text-center">
			<span>Selanjutnya</span>
			<i class="fa-solid fa-arrow-right text-[10px]"></i>
		</a>

		@if (auth()->user()->hasAnyRole(['admin_opd', 'super_admin']) && $allGreen)
			<button type="button" wire:click="startSppdLanjutan({{ $sppd->id }})" wire:loading.attr="disabled"
				class="inline-flex items-center justify-center gap-1.5 rounded bg-emerald-600 px-2 py-1.5 text-[11px] font-bold text-white shadow-2xs transition hover:bg-emerald-700 w-full text-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
				<i class="fa-solid fa-plus text-[10px]" wire:loading.remove wire:target="startSppdLanjutan({{ $sppd->id }})"></i>
				<i class="fa-solid fa-circle-notch fa-spin text-[10px]" wire:loading wire:target="startSppdLanjutan({{ $sppd->id }})"></i>
				<span>SPPD Lanjutan</span>
			</button>
		@endif
	@endif

	@if (auth()->user()->hasRole('super_admin'))
		<button type="button" wire:click="confirmDelete({{ $sppd->id }})" title="Hapus SPPD"
			class="inline-flex items-center justify-center gap-1.5 rounded border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] font-semibold text-red-600 transition hover:bg-red-100 w-full text-center">
			<i class="fa-solid fa-trash text-[10px]"></i>
			<span>Hapus</span>
		</button>
	@elseif ($sppd->status->value === 'in_progress' && auth()->user()->hasRole('admin_opd'))
		<button type="button" wire:click="confirmDelete({{ $sppd->id }})" title="Batalkan Pengajuan"
			class="inline-flex items-center justify-center gap-1.5 rounded border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] font-semibold text-red-600 transition hover:bg-red-100 w-full text-center">
			<i class="fa-solid fa-trash text-[10px]"></i>
			<span>Batalkan</span>
		</button>
	@endif
</div>
