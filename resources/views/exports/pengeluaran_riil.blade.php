<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Laporan Pengeluaran Riil</title>
	<style>
		@page { margin: 1.5cm 2cm; size: 21.5cm 33cm; }
		body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.4; }
		table { border-collapse: collapse; }
		.title { text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; text-transform: uppercase; margin-bottom: 5px; }
		.subtitle { text-align: center; font-size: 11pt; margin-bottom: 20px; }
		.info-table td { padding: 2px 4px; vertical-align: top; }
		.info-label { width: 180px; }
		.info-sep { width: 15px; text-align: center; }
		.data-table { width: 100%; margin-top: 15px; border: 1px solid #000; }
		.data-table th, .data-table td { border: 1px solid #000; padding: 5px 8px; }
		.data-table th { background: #f5f5f5; font-weight: bold; text-align: center; font-size: 10pt; }
		.text-right { text-align: right; }
		.text-center { text-align: center; }
		.bold { font-weight: bold; }
		.signature-table { width: 100%; margin-top: 30px; }
		.signature-table td { vertical-align: top; text-align: center; padding: 5px; }
		.underline { text-decoration: underline; }
	</style>
</head>
<body>
	<div class="title">DAFTAR PENGELUARAN RIIL</div>

	<p style="text-align: justify; margin-top: 15px;">
		Yang bertanda tangan di bawah ini menyatakan dengan sesungguhnya bahwa:
	</p>

	<table class="info-table" style="width: 100%; margin-top: 5px;">
		<tr>
			<td class="info-label">Nama</td>
			<td class="info-sep">:</td>
			<td><strong>{{ $targetUser->name }}</strong></td>
		</tr>
		@if($targetUser->nip)
		<tr>
			<td class="info-label">NIP</td>
			<td class="info-sep">:</td>
			<td>{{ $targetUser->nip }}</td>
		</tr>
		@endif
		<tr>
			<td class="info-label">Jabatan</td>
			<td class="info-sep">:</td>
			<td>{{ $targetUser->position->name ?? ($targetUser->roles->first()->name ?? '-') }}</td>
		</tr>
	</table>

	<p style="text-align: justify; margin-top: 10px;">
		Berdasarkan Surat Perintah Tugas (SPT) tanggal {{ $sppd->spt_date?->translatedFormat('d F Y') ?? '-' }}
		Nomor: {{ $sppd->document_number ?? '-' }},
		dengan ini kami menyatakan dengan sesungguhnya bahwa biaya-biaya pengeluaran riil di bawah ini
		yang tidak dapat diperoleh bukti-bukti pengeluarannya.
	</p>

	<table class="data-table">
		<thead>
			<tr>
				<th style="width: 35px;">No</th>
				<th>Uraian</th>
				<th style="width: 160px;">Jumlah (Rp)</th>
			</tr>
		</thead>
		<tbody>
			@php $total = 0; @endphp
			@foreach($expenses as $i => $expense)
				@php $total += $expense->amount; @endphp
				<tr>
					<td class="text-center">{{ $i + 1 }}</td>
					<td>{{ $expense->description }}</td>
					<td class="text-right">{{ number_format($expense->amount, 0, ',', '.') }}</td>
				</tr>
			@endforeach
			<tr>
				<td colspan="2" class="text-right bold" style="padding-right: 10px;">JUMLAH</td>
				<td class="text-right bold">{{ number_format($total, 0, ',', '.') }}</td>
			</tr>
		</tbody>
	</table>

	<p style="margin-top: 10px; font-style: italic;">
		Terbilang: <strong>{{ \App\Helpers\Terbilang::rupiah($total) }}</strong>
	</p>

	<p style="text-align: justify; margin-top: 10px;">
		Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.
	</p>

	<table class="signature-table">
		<tr>
			<td style="width: 50%;">
				<p>Mengetahui/Menyetujui</p>
				<p><strong>{{ $pdfData['pptk_role'] ?? 'PPTK' }}</strong></p>
				<div style="height: 60px;"></div>
				<p class="bold underline">{{ $pdfData['pptk_name'] ?? '.............................' }}</p>
				@if($pdfData['pptk_nip'] ?? null)
					<p>NIP. {{ $pdfData['pptk_nip'] }}</p>
				@endif
			</td>
			<td style="width: 50%;">
				<p>Kendari, {{ now()->translatedFormat('d F Y') }}</p>
				<p><strong>Yang Menyatakan</strong></p>
				<div style="height: 60px;"></div>
				<p class="bold underline">{{ $targetUser->name }}</p>
				@if($targetUser->nip)
					<p>NIP. {{ $targetUser->nip }}</p>
				@endif
			</td>
		</tr>
	</table>
</body>
</html>
