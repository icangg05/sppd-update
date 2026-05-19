@extends('layouts.app')
@section('title', 'Laporan Perjalanan')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">LAPORAN HASIL PERJALANAN DINAS</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
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
				<div>
					<label class="form-label font-bold text-slate-700">Tanggal Laporan</label>
					<input type="date" name="report_date" class="form-input max-w-xs" value="{{ $sppd->report->report_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
				</div>

				<div>
					<label class="form-label font-bold text-slate-700">Hasil Perjalanan / Laporan Narasi</label>
					<textarea name="report_text" class="form-textarea" rows="15" placeholder="Masukkan detail hasil perjalanan dinas secara lengkap..." required>{{ $sppd->report->report_text ?? '' }}</textarea>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="form-label font-bold text-slate-700">File Laporan <span class="text-slate-400 font-normal">(opsional, max 20MB)</span></label>
						<input type="file" name="report_file" class="form-input" accept=".pdf,.doc,.docx">
						@if($sppd->report?->report_file)
							<p class="text-xs text-emerald-600 mt-1">✓ File sudah diupload: <a href="{{ asset('storage/' . $sppd->report->report_file) }}" target="_blank" class="underline">Lihat</a></p>
						@endif
					</div>
					<div>
						<label class="form-label font-bold text-slate-700">Foto Dokumentasi <span class="text-slate-400 font-normal">(opsional, max 20MB)</span></label>
						<input type="file" name="documentation_file" class="form-input" accept="image/*">
						@if($sppd->report?->documentation_file)
							<p class="text-xs text-emerald-600 mt-1">✓ Foto sudah diupload: <a href="{{ asset('storage/' . $sppd->report->documentation_file) }}" target="_blank" class="underline">Lihat</a></p>
						@endif
					</div>
				</div>

				<div class="flex justify-end gap-3 pt-2">
					<button type="submit" class="btn-primary px-8">
						{{ $sppd->report ? 'Perbarui Laporan' : 'Simpan Laporan' }}
					</button>
				</div>
			</div>
		</form>
	</div>
@endsection
