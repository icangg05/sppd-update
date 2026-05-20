<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Pengeluaran Riil - {{ config('app.name') }}</title>
	<style>
		@page {
			margin: 1.5cm 2.3cm;
			size: 21.5cm 33cm;
		}

		body {
			font-family: 'Times New Roman', Times, serif;
			font-size: 11pt;
			color: #000;
			line-height: 1.4;
		}

		table {
			border-collapse: collapse;
		}

		.title {
			text-align: center;
			font-weight: bold;
			font-size: 14pt;
			text-decoration: underline;
			text-transform: uppercase;
			margin-bottom: 5px;
		}

		.subtitle {
			text-align: center;
			font-size: 11pt;
			margin-bottom: 20px;
		}

		.info-table td {
			vertical-align: top;
		}

		.info-label {
			width: 80px;
		}

		.info-sep {
			width: 15px;
			text-align: center;
		}

		.data-table {
			width: 100%;
			border: 1px solid #000;
			font-size: 9pt;
		}

		.data-table th,
		.data-table td {
			border: 1px solid #000;
			padding: 3px 8px;
		}

		.data-table th {
			background: #f5f5f5;
			font-weight: bold;
			text-align: center;
			font-size: 10pt;
		}

		.text-right {
			text-align: right;
		}

		.text-center {
			text-align: center;
		}

		.bold {
			font-weight: bold;
		}

		.signature-table {
			width: 100%;
		}

		.signature-table td {
			vertical-align: top;
			text-align: center;
		}

		.underline {
			text-decoration: underline;
		}

		.bline {
			border: 1px solid red !important;
		}

		ol {
			margin: 5px 0;
			padding-left: 1.2em;
			/* hanya ruang untuk nomor, konten tidak masuk ke padding */
		}

		ol li {
			margin-bottom: 6px;
			padding-left: 4px;
		}

		.header {
			text-align: center;
			margin-bottom: 16px;
			border-bottom: 3px double #000;
			padding-bottom: 10px;
		}

		.logo {
			width: 80px;
			height: auto;
			margin-bottom: 10px;
		}
	</style>
</head>

<body>
	<!-- QR Code — scan untuk verifikasi dokumen -->
	<div style="position: absolute; top: -40px; right: -70px;">
		<img src="{{ $pdfData['qr_image'] }}" style="width: 85px; height: 85px;" alt="QR Code">
	</div>

	<div class="header"
		style="{{ $pdfData['is_walikota'] || ($sppd->user->department->letterhead && \Illuminate\Support\Str::contains($sppd->user->department->letterhead, '/')) ? 'border-bottom: none; padding-bottom: 0;' : '' }}">
		@if ($pdfData['is_walikota'])
			<div style="text-align: center; margin-bottom: 10px;">
				<img src="{{ public_path('img/garuda.png') }}" style="width: 110px; height: auto;">
			</div>
			<div style="margin-top: 22px; font-size: 22pt; font-weight: bold; text-transform: uppercase;">WALIKOTA KENDARI</div>
		@elseif ($sppd->user->department->letterhead && \Illuminate\Support\Str::contains($sppd->user->department->letterhead, '/'))
			<img src="{{ storage_path('app/public/' . $sppd->user->department->letterhead) }}"
				style="width: 100%; height: auto;">
		@elseif ($sppd->user->department->parent_id == null)
			{{-- Jika level OPD/Walikota --}}
			<img src="{{ public_path('img/aruda.png') }}" class="logo">
			<div style="font-size: 26pt; font-weight: bold;">WALIKOTA KENDARI</div>
		@else
			{{-- Jika level dinas --}}
			<div style="font-size: 14pt; font-weight: bold;">PEMERINTAH KOTA KENDARI</div>
			<div style="font-size: 16pt; font-weight: bold; text-transform: uppercase;">{{ $sppd->user->department->name }}</div>
			<div style="font-size: 10pt;">{{ $sppd->user->department->address ?? '' }}</div>
		@endif
	</div>

	<div class="title" style="margin-top: 15px;">DAFTAR PENGELUARAN RIIL</div>

	<p style="text-align: justify; margin-top: 15px; margin-bottom: 5px; font-size: 9pt;">
		Yang bertanda tangan di bawah ini :
	</p>

	<table class="info-table" style="width: 100%; font-size: 9pt;">
		<tr>
			<td class="info-label">Nama</td>
			<td class="info-sep">:</td>
			<td>{{ $targetUser->name }}</td>
		</tr>
		@if ($targetUser->nip)
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

	<p style="text-align: justify; margin-top: 10px; font-size: 9pt;">
		Berdasarkan Surat Perintah Perjalanan Dinas (SPPD) tanggal {{ $sppd->sppd_date?->translatedFormat('d F Y') ?? '-' }}
		Nomor {{ $sppd->document_number ?? '___________________________' }} dengan ini kami menyatakan dengan sesungguhnya
		bahwa :
	</p>

	<ol style="font-size: 9pt;">
		<li>
			Biaya transport pegawai dibawah ini yang tidak dapat diperoleh bukti-bukti pengeluarannya, meliputi :
			<table class="data-table" style="margin-top: 10px; margin-bottom: 10px;">
				<thead>
					<tr>
						<th style="width: 5px;">No.</th>
						<th>Uraian</th>
						<th style="width: 160px;">Jumlah (Rp)</th>
					</tr>
				</thead>
				<tbody>
					@php $total = 0; @endphp
					@foreach ($expenses as $i => $expense)
						@php $total += $expense->amount; @endphp
						<tr>
							<td class="text-center">{{ $i + 1 }}.</td>
							<td>{{ $expense->description }}</td>
							<td class="text-right">{{ number_format($expense->amount, 0, ',', '.') }}</td>
						</tr>
					@endforeach
					<tr>
						<td colspan="2" class="text-right bold" style="padding-right: 10px;">Jumlah</td>
						<td class="text-right bold">{{ number_format($total, 0, ',', '.') }}</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan
			apabila dikemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas
			Daerah.
		</li>
	</ol>

	<p style="text-align: justify; margin-top: 10px; font-size: 9pt;">
		Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.
	</p>

	<p style="text-align: right; margin-top: 20px; margin-bottom: 0px; font-size: 9pt;">
		Kendari, {{ now()->translatedFormat('d F Y') }}
	</p>

	<table class="signature-table" style="font-size: 9pt;">
		<tr>
			<td style="width: 50%;">
				<p>
					Menyetujui :<br>
					Pejabat Pelaksana Teknis Kegiatan
				</p>
				<div style="height: 50px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $pdfData['pptk_name'] }}</span><br />
					@if ($pdfData['pptk_nip'] ?? null)
						NIP. {{ $pdfData['pptk_nip'] }}
					@endif
				</p>
			</td>
			<td style="width: 50%;">
				<p><br />Yang Melakukan Perjalanan Dinas</p>
				<div style="height: 50px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $targetUser->name }}</span><br />
					@if ($targetUser->nip)
						NIP. {{ $targetUser->nip }}
					@endif
				</p>
			</td>
		</tr>
		<tr>
			<td style="width: 100%; padding-top: 15px;" colspan="2">
				<p>
					Mengetahui :<br>
					{{ $pdfData['pimpinan_role'] }}
				</p>
				<div style="height: 50px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $pdfData['pimpinan_name'] }}</span><br />
					@if ($pdfData['pimpinan_nip'] ?? null)
						NIP. {{ $pdfData['pimpinan_nip'] }}
					@endif
				</p>
			</td>
		</tr>
	</table>
</body>

</html>
