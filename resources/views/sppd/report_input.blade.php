@extends('layouts.app')
@section('title', 'Laporan Perjalanan')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">LAPORAN HASIL PERJALANAN DINAS</h1>
		</div>
		<x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary">Kembali</x-ui.button>
	</div>

	<div class="card p-6 border-slate-200">
		<div class="mb-6">
			<p class="text-sm font-medium text-slate-700">Pelaksana : <span class="font-bold uppercase">{{ $sppd->user->name }}</span></p>
			<p class="text-xs text-slate-500">Maksud Perjalanan : {{ $sppd->purpose }}</p>

			@if($sppd->report && $sppd->report->verification_status)
				<div class="mt-2">
					@php $vs = $sppd->report->verification_status->value; @endphp
					<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold
						{{ $vs === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($vs === 'returned' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
						{{ $vs === 'verified' ? '✓ Terverifikasi' : ($vs === 'returned' ? '↩ Dikembalikan' : '⏳ Menunggu Verifikasi') }}
					</span>
				</div>
			@endif
		</div>

		<form action="{{ route('sppd.report.store', $sppd) }}" method="POST" enctype="multipart/form-data">
			@csrf
			<div class="space-y-4">
				<x-form.input
					type="date"
					name="report_date"
					label="Tanggal Laporan"
					:value="$sppd->report->report_date?->format('Y-m-d') ?? now()->format('Y-m-d')"
					class="max-w-xs"
				/>

				<x-form.textarea
					name="report_text"
					label="Hasil Perjalanan / Laporan Narasi"
					:rows="15"
					placeholder="Masukkan detail hasil perjalanan dinas secara lengkap..."
					required
					:value="$sppd->report->report_text ?? ''"
				/>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<x-form.file
						name="report_file"
						label="File Laporan"
						accept=".pdf,.doc,.docx"
						hint="opsional, max 20MB"
					/>

					@if($sppd->report?->report_file)
						<p class="text-xs text-emerald-600 mt-1">✓ File sudah diupload: <a href="{{ asset('storage/' . $sppd->report->report_file) }}" target="_blank" class="underline">Lihat</a></p>
					@endif

					<x-form.file
						name="documentation_file"
						label="Foto Dokumentasi"
						accept="image/*"
						hint="opsional, max 20MB"
					/>

					@if($sppd->report?->documentation_file)
						<p class="text-xs text-emerald-600 mt-1">✓ Foto sudah diupload: <a href="{{ asset('storage/' . $sppd->report->documentation_file) }}" target="_blank" class="underline">Lihat</a></p>
					@endif
				</div>

				<div class="flex justify-end gap-3 pt-2">
					<x-ui.button type="submit" class="px-8">
						{{ $sppd->report ? 'Perbarui Laporan' : 'Simpan Laporan' }}
					</x-ui.button>
				</div>
			</div>
		</form>
	</div>
@endsection
