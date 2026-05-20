<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>RPBD - {{ config('app.name') }}</title>
	<style>
		@page {
			margin: 1.5cm 1.8cm;
			size: 21.5cm 33cm;
		}

		body {
			font-family: 'Times New Roman', Times, serif;
			font-size: 10pt;
			color: #000;
			line-height: 1.4;
		}

		table {
			border-collapse: collapse;
		}

		.title {
			text-align: center;
			font-weight: bold;
			font-size: 10pt;
			text-decoration: underline;
			text-transform: uppercase;
		}

		.info-table {
			font-size: 9pt;
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
			border-collapse: collapse;
		}

		.data-table th,
		.data-table td {
			border: 1px solid #000;
			padding: 3px 8px;
			font-size: 9pt;
			vertical-align: top;
		}

		.data-table th {
			background: #f5f5f5;
			font-weight: bold;
			text-align: center;
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
			font-size: 9pt;
		}

		.signature-table td {
			vertical-align: top;
			text-align: left;
		}

		.underline {
			text-decoration: underline;
		}

		.bline {
			border: 1px solid red !important
		}
	</style>
</head>

<body>
	<div style="position: absolute; top: -37px; right: -50px;">
		<img src="{{ $pdfData['qr_image'] }}" style="width: 85px; height: 85px;" alt="QR Code">
	</div>

	<div class="title" style="margin-bottom: 9px;">RINCIAN BIAYA PERJALANAN DINAS</div>

	<div style="font-size: 9pt; text-align: center; margin-bottom: 20px;">An. {{ $targetUser->name }}</div>

	<table class="info-table" style="width: 100%">
		<tr>
			<td class="info-label">Lampiran SPPD Nomor</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->document_number ?? '' }}</td>
		</tr>
		<tr>
			<td class="info-label">Tanggal</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->sppd_date?->translatedFormat('d F Y') ?? '-' }}</td>
		</tr>
	</table>

	<table class="data-table">
		<thead>
			<tr>
				<th style="width: 15px;">No.</th>
				<th>Rincian Belanja</th>
				<th style="width: 145px;">Jumlah</th>
				<th style="width: 100px;">Keterangan</th>
			</tr>
		</thead>
		<tbody>
			@php
				$no = 1;
				$grandTotal = $totalPengeluaranRiil;
			@endphp

			{{-- Baris 1: Pengeluaran Riil --}}
			@if ($totalPengeluaranRiil > 0)
				<tr>
					<td class="text-center">{{ $no++ }}.</td>
					<td>Pengeluaran Rill</td>
					<td>
						<table style="width:100%; border:none; border-collapse:collapse;">
							<tr>
								<td style="border:none; text-align:right; padding:0 2px;">
									Rp. {{ number_format($totalPengeluaranRiil, 0, ',', '.') }}</td>
							</tr>
						</table>
					</td>
					<td></td>
				</tr>
			@endif

			{{-- Baris selanjutnya: Cost Details --}}
			@foreach ($costs as $cost)
				@php $grandTotal += $cost->total; @endphp
				<tr>
					<td class="text-center">{{ $no++ }}.</td>
					<td>
						{{ $cost->description }}
						@if ($cost->airline_name)
							<br><small>Maskapai: {{ $cost->airline_name }}</small>
						@endif
						@if ($cost->ticket_number)
							<small> | Tiket: {{ $cost->ticket_number }}</small>
						@endif
						<br>{{ $cost->quantity }} x Rp. {{ number_format($cost->unit_cost, 0, ',', '.') }}
					</td>
					<td style="text-align:right;">Rp. {{ number_format($cost->total, 0, ',', '.') }}</td>
					<td></td>
				</tr>
			@endforeach

			{{-- Baris total --}}
			<tr>
				<td></td>
				<td class="text-center bold">JUMLAH</td>
				<td style="text-align:right; font-weight:bold;">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
				<td></td>
			</tr>
		</tbody>
	</table>

	<table class="signature-table" style="margin-top: 5px;">
		<tr>
			<td style="width: 50%; line-height: 1.7;">
				<p>
					<br />
					Telah dibayarkan Sejumlah<br />
					Rp. {{ number_format($grandTotal, 0, ',', '.') }}<br />
					Bendahara Pengeluaran
				</p>
				<div style="height: 35px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $pdfData['bendahara_name'] }}</span>
					@if ($pdfData['bendahara_nip'] ?? null)
						<br />NIP. {{ $pdfData['bendahara_nip'] }}
					@endif
				</p>
			</td>
			<td style="width: 50%; line-height: 1.7;">
				<p>
					Kendari, {{ now()->translatedFormat('d F Y') }}<br />
					Telah Menerima Jumlah Uang Sebesar,<br />
					Rp. {{ number_format($grandTotal, 0, ',', '.') }}<br />
					Yang Menerima
				</p>
				<div style="height: 35px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $targetUser->name }}</span>
					@if ($targetUser->nip)
						<br />NIP. {{ $targetUser->nip }}
					@endif
				</p>
			</td>
		</tr>
	</table>

	<div style="border-top: 1px solid black; width: 100%; margin: 10px 0 20px 0;"></div>

	<div style="font-size: 9pt; text-align: center; margin-bottom: 20px;">
		PERHITUNGAN SPPD RAMPUNG
	</div>

	@php
		$panjar = $pdfData['panjar_amount'] ?? 0;
		$sisa = $grandTotal - $panjar;
	@endphp

	<table class="info-table" style="width: 100%">
		<tr>
			<td class="info-label">Ditetapkan Sejumlah</td>
			<td class="info-sep">:</td>
			<td>Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
		</tr>
		<tr>
			<td class="info-label">Yang telah dibayar semula</td>
			<td class="info-sep">:</td>
			<td>Rp. {{ number_format($panjar, 0, ',', '.') }}</td>
		</tr>
		<tr>
			<td class="info-label">Sisa Kurang / Lebih</td>
			<td class="info-sep">:</td>
			<td>Rp. {{ number_format($sisa, 0, ',', '.') }}</td>
		</tr>
	</table>

	<div style="font-size: 9pt; text-align: center; margin: 15px 0 5px 0; font-style: italic;">
		Terbilang: {{ \App\Helpers\Terbilang::rupiah($sisa) }}
	</div>

	<table class="signature-table">
		<tr>
			<td style="width: 50%; line-height: 1.7;">
				<p>
					Setuju bayar:<br />
					{{ $pdfData['pimpinan_role'] }}
				</p>
				<div style="height: 35px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $pdfData['pimpinan_name'] }}</span>
					@if ($pdfData['pimpinan_nip'] ?? null)
						<br />NIP. {{ $pdfData['pimpinan_nip'] }}
					@endif
				</p>
			</td>
			<td style="width: 50%; line-height: 1.7;">
				<p>
					Mengetahui/Menyetujui<br />
					Pejabat Pelaksana Teknis Kegiatan
				</p>
				<div style="height: 35px;"></div>
				<p>
					<span class="bold" style="text-decoration: underline;">{{ $pdfData['pptk_name'] }}</span>
					@if ($pdfData['pptk_nip'] ?? null)
						<br />NIP. {{ $pdfData['pptk_nip'] }}
					@endif
				</p>
			</td>
		</tr>
	</table>
</body>

</html>
