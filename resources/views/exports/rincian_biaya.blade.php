<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Rincian Biaya Perjalanan Dinas</title>
	<style>
		@page { margin: 1.5cm 2cm; size: 21.5cm 33cm; }
		body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; color: #000; line-height: 1.4; }
		table { border-collapse: collapse; }
		.title { text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; text-transform: uppercase; margin-bottom: 20px; }
		.info-table td { padding: 2px 4px; vertical-align: top; }
		.info-label { width: 180px; }
		.info-sep { width: 15px; text-align: center; }
		.data-table { width: 100%; margin-top: 15px; border: 1px solid #000; }
		.data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; font-size: 9pt; }
		.data-table th { background: #f5f5f5; font-weight: bold; text-align: center; }
		.text-right { text-align: right; }
		.text-center { text-align: center; }
		.bold { font-weight: bold; }
		.signature-table { width: 100%; margin-top: 30px; }
		.signature-table td { vertical-align: top; text-align: center; padding: 5px; }
		.underline { text-decoration: underline; }
	</style>
</head>
<body>
	<div class="title">RINCIAN BIAYA PERJALANAN DINAS</div>

	<table class="info-table" style="width: 100%">
		<tr>
			<td class="info-label">Lampiran SPT Nomor</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->document_number ?? '-' }}</td>
		</tr>
		<tr>
			<td class="info-label">Tanggal</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->spt_date?->translatedFormat('d F Y') ?? '-' }}</td>
		</tr>
		<tr>
			<td class="info-label">Nama Pelaksana</td>
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
			<td class="info-label">Maksud Perjalanan</td>
			<td class="info-sep">:</td>
			<td>{{ $sppd->purpose }}</td>
		</tr>
	</table>

	<table class="data-table">
		<thead>
			<tr>
				<th style="width: 30px;">No</th>
				<th>Perincian Biaya</th>
				<th style="width: 70px;">Jumlah Satuan</th>
				<th style="width: 120px;">Harga Satuan (Rp)</th>
				<th style="width: 120px;">Jumlah (Rp)</th>
			</tr>
		</thead>
		<tbody>
			@php $grandTotal = 0; @endphp
			@foreach($costs as $i => $cost)
				@php $grandTotal += $cost->total; @endphp
				<tr>
					<td class="text-center">{{ $i + 1 }}</td>
					<td>
						<strong>{{ $cost->cost_category->label() }}</strong><br>
						{{ $cost->description }}
						@if($cost->airline_name)
							<br><small>Maskapai: {{ $cost->airline_name }}</small>
						@endif
						@if($cost->ticket_number)
							<small> | Tiket: {{ $cost->ticket_number }}</small>
						@endif
					</td>
					<td class="text-center">{{ $cost->quantity }}</td>
					<td class="text-right">{{ number_format($cost->unit_cost, 0, ',', '.') }}</td>
					<td class="text-right">{{ number_format($cost->total, 0, ',', '.') }}</td>
				</tr>
			@endforeach
			<tr>
				<td colspan="4" class="text-right bold" style="padding-right: 10px;">JUMLAH</td>
				<td class="text-right bold">{{ number_format($grandTotal, 0, ',', '.') }}</td>
			</tr>
		</tbody>
	</table>

	<p style="margin-top: 10px; font-style: italic;">
		Terbilang: <strong>{{ \App\Helpers\Terbilang::rupiah($grandTotal) }}</strong>
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
