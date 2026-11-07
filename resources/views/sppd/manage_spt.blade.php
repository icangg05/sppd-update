@extends('layouts.app')
@section('title', 'Kelola SPT')

@section('content')
	@php
		$sptSignature = $sppd->signatureFor('spt');
		$sptStatus = $sptSignature?->status->value;
		$isSigned = $sptStatus === 'signed' && $sptSignature->signed_file_path;
		$sptUrl = $isSigned ? Storage::url($sptSignature->signed_file_path) : route('sppd.stream.spt', $sppd);
		$sptDate = $sppd->spt_date ?? $sppd->created_at;
	@endphp

	<div class="mx-auto max-w-6xl space-y-4 p-1">

		<x-sppd.doc-header :sppd="$sppd" title="Surat Perintah Tugas"
			description="Cetak SPT dan pantau status tanda tangan elektroniknya." />

		<x-sppd.panel title="Dokumen SPT" icon="fa-file-signature">
			<dl class="grid grid-cols-1 gap-px bg-slate-200 sm:grid-cols-2">
				<div class="bg-white px-5 py-3">
					<dt class="text-xs font-medium text-slate-500">Pelaksana tugas</dt>
					<dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $sppd->user->name }}</dd>
					@if ($sppd->user->nip)
						<dd class="font-mono text-xs text-slate-500">{{ $sppd->user->nip }}</dd>
					@endif
				</div>
				<div class="bg-white px-5 py-3">
					<dt class="text-xs font-medium text-slate-500">Tanggal dokumen</dt>
					<dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $sptDate->translatedFormat('d F Y') }}</dd>
				</div>
				<div class="bg-white px-5 py-3">
					<dt class="text-xs font-medium text-slate-500">Status TTE</dt>
					<dd class="mt-1"><x-sppd.tte-badge :status="$sptStatus" /></dd>
					@if ($sptStatus === 'rejected' && $sptSignature->error_message)
						<dd class="mt-1 text-xs text-rose-700">{{ $sptSignature->error_message }}</dd>
					@endif
				</div>
				<div class="bg-white px-5 py-3">
					<dt class="text-xs font-medium text-slate-500">Penandatangan</dt>
					@if ($sptStatus === 'signed' && $sptSignature->signer)
						<dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $sptSignature->signer->name }}</dd>
						<dd class="text-xs text-slate-500">
							@if ($sptSignature->signer->nip)<span class="font-mono">{{ $sptSignature->signer->nip }}</span> · @endif
							{{ $sptSignature->signed_at?->translatedFormat('d M Y H:i') }} WITA
						</dd>
					@else
						<dd class="mt-0.5 text-sm text-slate-500">Belum ditandatangani</dd>
					@endif
				</div>
			</dl>

			<div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
				<p class="text-xs text-slate-600">
					<i class="fa-solid fa-circle-info mr-1 text-slate-500"></i>
					{{ $isSigned ? 'Yang dibuka adalah dokumen SPT yang sudah ditandatangani elektronik.' : 'Dokumen PDF siap cetak. Setelah TTE selesai, tombol ini membuka dokumen bertanda tangan.' }}
				</p>
				<x-sppd.print-link :href="$sptUrl" size="md">{{ $isSigned ? 'Buka SPT Bertanda Tangan' : 'Cetak SPT' }}</x-sppd.print-link>
			</div>
		</x-sppd.panel>
	</div>

	@if ($sptStatus === 'processing')
		@include('sppd.partials.tte-poll')
	@endif
@endsection
