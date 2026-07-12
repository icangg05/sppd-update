{{-- Badge status + progres realisasi/laporan. Dipakai tabel desktop & kartu mobile.
     Param: $sppd, opsional $stackClass (lebar kolom progres). --}}
@php
	$statusBadge = match ($sppd->status->value) {
	    'draft' => ['bg' => 'bg-amber-50 border-amber-200', 'text' => 'text-amber-700', 'label' => 'Masuk'],
	    'in_progress' => $sppd->revision_note
	        ? ['bg' => 'bg-orange-50 border-orange-200', 'text' => 'text-orange-700', 'label' => 'Revisi']
	        : ['bg' => 'bg-blue-50 border-blue-200', 'text' => 'text-blue-700', 'label' => 'Proses'],
	    'approved', 'completed', 'verified', 'signed' => ['bg' => 'bg-emerald-50 border-emerald-200', 'text' => 'text-emerald-700', 'label' => 'Selesai'],
	    'rejected' => ['bg' => 'bg-red-50 border-red-200', 'text' => 'text-red-700', 'label' => 'Ditolak'],
	    'pending', 'revision' => ['bg' => 'bg-orange-50 border-orange-200', 'text' => 'text-orange-700', 'label' => 'Revisi'],
	    'returned' => ['bg' => 'bg-pink-50 border-pink-200', 'text' => 'text-pink-700', 'label' => 'Kembali'],
	    default => ['bg' => 'bg-slate-50 border-slate-200', 'text' => 'text-slate-700', 'label' => $sppd->status->label()],
	};
@endphp

@if (in_array($sppd->status->value, ['approved', 'completed']))
	@if (today()->lessThanOrEqualTo($sppd->end_date))
		<span class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide">
			Perjalanan Disetujui
		</span>
	@else
		<div class="flex flex-col gap-1 {{ $stackClass ?? 'w-[180px]' }}">
			<span class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center whitespace-normal leading-tight">
				Perjalanan Selesai dan Masukkan Laporan
			</span>

			@if ($sppd->actualExpenses->isNotEmpty() && $sppd->costDetails->isNotEmpty())
				<span class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center">Sudah Realisasi</span>
			@else
				<span class="inline-block rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[9px] font-bold text-red-700 tracking-wide text-center">Belum Realisasi</span>
			@endif

			@if ($sppd->report && $sppd->report->report_date && $sppd->report->report_file && $sppd->report->documentation_file)
				<span class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center">Sudah Upload Laporan</span>
			@else
				<span class="inline-block rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[9px] font-bold text-red-700 tracking-wide text-center">Belum Upload Laporan</span>
			@endif
		</div>
	@endif
@else
	<span class="inline-block rounded border px-1.5 py-0.5 text-[9px] font-bold tracking-wide {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }}">
		{{ \App\Helpers\SmartTitle::convert($statusBadge['label']) }}
	</span>
@endif
