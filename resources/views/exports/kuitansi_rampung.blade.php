<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Kuitansi Rampung - {{ config('app.name') }}</title>
	<style>
		@page {
			margin: 2cm 1.5cm;
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
			margin-bottom: 20px;
			font-style: italic;
			margin-top: 20px;
		}

		.info-table td {
			padding: 2px 4px;
			vertical-align: top;
		}

		.info-label {
			width: 180px;
		}

		.info-sep {
			width: 15px;
			text-align: center;
		}

		.data-table {
			width: 100%;
			margin-top: 15px;
			border: 1px solid #000;
		}

		.data-table th,
		.data-table td {
			border: 1px solid #000;
			padding: 5px 8px;
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
			text-align: left;
		}

		.underline {
			text-decoration: underline;
		}

		.bline {
			border: 1px solid red !important;
		}
	</style>
</head>

<body>
	<!-- QR Code — scan untuk membuka kembali halaman ini -->
	<div style="position: absolute; top: -1.5cm; right: -1cm">
		<img src="{{ $pdfData['qr_image'] }}" alt="QR Code">
	</div>

	<!-- Nama dinas & keterangan (tahun, koded, bku, tanggal) -->
	<table style="width: 98%; border-collapse: collapse; font-size: 9pt;">
		<tr>
			<td style="width: 65%; vertical-align: top;">
				<p style="margin: 4px;">
					PEMERINTAH KOTA KENDARI<br />
					{{ strtoupper($pdfData['dept_name']) }}
				</p>
			</td>
			<td style="width: 35%; vertical-align: top;">
				<table style="width: 100%;">
					<tr>
						<td style="width: 130px;">TAHUN ANGGARAN</td>
						<td class="info-sep">:</td>
						<td style="width: 105px;">{{ $pdfData['tahun_anggaran'] }}</td>
					</tr>
					<tr>
						<td style="width: 130px;">KODE REKENING</td>
						<td class="info-sep">:</td>
						<td style="width: 120px;">{{ $pdfData['kode_rekening'] }}</td>
					</tr>
					<tr>
						<td style="width: 130px;">BKU NO.</td>
						<td class="info-sep">:</td>
						<td style="width: 105px;">{{ $pdfData['bku'] ?? '-' }}</td>
					</tr>
					<tr>
						<td style="width: 130px;">TANGGAL</td>
						<td class="info-sep">:</td>
						<td style="width: 105px;">{{ $pdfData['date'] ?? '-' }}</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<div class="title">KUITANSI</div>

	<table class="info-table" style="width: 100%; font-size: 9pt;">
		<tr>
			<td class="info-label">SUDAH TERIMA DARI</td>
			<td class="info-sep">:</td>
			<td>Pengguna Anggaran {{ $pdfData['dept_name'] }}</td>
		</tr>
		<tr>
			<td class="info-label">UANG SEBESAR</td>
			<td class="info-sep">:</td>
			<td>Rp. {{ number_format($pdfData['uang_sebesar'], 0, ',', '.') }}</td>
		</tr>
		<tr>
			<td class="info-label">UNTUK PEMBAYARAN</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->purpose }}</td>
		</tr>
	</table>

	<!-- Terbilang rupiah -->
	<div
		style="font-size: 9pt; margin-top: 20px; margin-bottom: 5px; padding: 3px; border: 1px solid black; font-weight: bold; text-align: center;">
		<p style="margin: 0;">TERBILANG : {{ $pdfData['terbilang_uang'] }}</p>
	</div>


	<!-- Tanggal di sebelah kanan -->
	<p style="text-transform: uppercase; text-align: right; margin-top: 20px; margin-bottom: 0; font-size: 9pt;">
		Kendari, {{ $pdfData['date'] ?? now()->translatedFormat('d F Y') }}
	</p>


	<!-- Bagian tanda tangan & tanggal -->
	<table class="signature-table" style="font-size: 9pt;">
		<tr>
			<td style="width: 33%;">
				<p>SETUJU BAYAR<br />{{ strtoupper($pdfData['approver_label'] ?? 'PENGGUNA ANGGARAN') }}</p>
				<div style="height: 55px;"></div>
				<p>
					<span style="font-weight: bold; text-decoration: underline;">
						{{ $pdfData['approver_name'] ?? '_________________________' }}
					</span>
					<br>
					@if ($pdfData['approver_nip'] ?? null)
						NIP. {{ $pdfData['approver_nip'] }}
					@endif
				</p>
			</td>
			<td style="width: 33%;">
				<p><br />BENDAHARA PENGELUARAN</p>
				<div style="height: 55px;"></div>
				<p>
					<span style="font-weight: bold; text-decoration: underline;">
						{{ $pdfData['bendahara_name'] }}
					</span>
					<br>
					@if ($pdfData['bendahara_nip'] ?? null)
						NIP. {{ $pdfData['bendahara_nip'] }}
					@endif
				</p>
			</td>
			<td style="width: 33%;">
				<p><br />YANG MENERIMA</p>
				<div style="height: 55px;"></div>
				<p>
					<span style="font-weight: bold; text-decoration: underline;">
						{{ $targetUser->name }}
					</span>
					<br>
					@if ($targetUser->nip)
						NIP. {{ $targetUser->nip }}
					@endif
				</p>
			</td>
		</tr>
	</table>
</body>

</html>
