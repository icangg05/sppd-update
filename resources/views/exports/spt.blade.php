<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style>
		@page {
			margin: 2cm;
		}

		body {
			font-family: 'Times New Roman', Times, serif;
			line-height: 1.5;
			font-size: 12pt;
			color: #000;
		}

		.header {
			text-align: center;
			margin-bottom: 20px;
			border-bottom: 3px double #000;
			padding-bottom: 10px;
		}

		.logo {
			width: 80px;
			height: auto;
			margin-bottom: 10px;
		}

		.title {
			text-align: center;
			text-decoration: underline;
			font-weight: bold;
			font-size: 14pt;
			margin-top: 20px;
			text-transform: uppercase;
		}

		.subtitle {
			text-align: center;
			font-weight: bold;
			margin-bottom: 30px;
		}

		.content-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
			vertical-align: top;
		}

		.content-table td {
			vertical-align: top;
			padding: 5px 0;
		}

		.label {
			width: 80px;
		}

		.separator {
			width: 20px;
			text-align: center;
		}

		.ordered-list {
			margin: 0;
			padding-left: 20px;
		}

		.footer {
			margin-top: 50px;
			width: 100%;
		}

		.signature-wrap {
			float: right;
			width: 250px;
			text-align: left;
		}

		.tembusan {
			margin-top: 40px;
			font-size: 10pt;
		}

		.bold {
			font-weight: bold;
		}

		.uppercase {
			text-transform: uppercase;
		}

		.clear {
			clear: both;
		}
	</style>
</head>

<body>
	<div class="header">
		@if ($sppd->user->department->parent_id == null)
			{{-- Jika level OPD/Walikota --}}
			<img src="{{ public_path('images/logo-garuda.png') }}" class="logo">
			<div style="font-size: 16pt; font-weight: bold;">WALIKOTA KENDARI</div>
		@else
			{{-- Jika level dinas --}}
			<div style="font-size: 14pt; font-weight: bold;">PEMERINTAH KOTA KENDARI</div>
			<div style="font-size: 16pt; font-weight: bold; text-transform: uppercase;">{{ $sppd->user->department->name }}</div>
			<div style="font-size: 10pt;">{{ $sppd->user->department->address ?? '' }}</div>
		@endif
	</div>

	<div class="title">SURAT PERINTAH TUGAS</div>
	<div class="subtitle">Nomor : {{ $sppd->document_number ?? '......../......../........' }}</div>

	<table class="content-table">
		<tr>
			<td class="label">Dari</td>
			<td class="separator">:</td>
			<td class="bold uppercase">{{ $sppd->user->department->parent->name ?? 'Walikota Kendari' }}</td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: center; padding: 20px 0; font-weight: bold;">MEMERINTAHKAN</td>
		</tr>
		<tr>
			<td class="label">Kepada</td>
			<td class="separator">:</td>
			<td>
				<table style="width: 100%;">
					{{-- Pelaksana Utama --}}
					<tr>
						<td style="width: 20px;">1.</td>
						<td style="width: 100px;">Nama</td>
						<td style="width: 10px;">:</td>
						<td class="bold uppercase">{{ $sppd->user->name }}</td>
					</tr>
					<tr>
						<td></td>
						<td>Pangkat/Gol</td>
						<td>:</td>
						<td>{{ $sppd->user->rank->name ?? '-' }}</td>
					</tr>
					<tr>
						<td></td>
						<td>NIP</td>
						<td>:</td>
						<td>{{ $sppd->user->nip }}</td>
					</tr>
					<tr>
						<td></td>
						<td>Jabatan</td>
						<td>:</td>
						<td>{{ $sppd->user->position_name }}</td>
					</tr>

					{{-- Pengikut --}}
					@foreach ($sppd->followers as $index => $follower)
						<tr>
							<td style="padding-top: 15px;">{{ $index + 2 }}.</td>
							<td style="padding-top: 15px;">Nama</td>
							<td style="padding-top: 15px;">:</td>
							<td class="bold uppercase" style="padding-top: 15px;">{{ $follower->user->name }}</td>
						</tr>
						<tr>
							<td></td>
							<td>Pangkat/Gol</td>
							<td>:</td>
							<td>{{ $follower->user->rank->name ?? '-' }}</td>
						</tr>
						<tr>
							<td></td>
							<td>NIP</td>
							<td>:</td>
							<td>{{ $follower->user->nip }}</td>
						</tr>
						<tr>
							<td></td>
							<td>Jabatan</td>
							<td>:</td>
							<td>{{ $follower->user->position_name }}</td>
						</tr>
					@endforeach
				</table>
			</td>
		</tr>
		<tr>
			<td class="label" style="padding-top: 20px;">Untuk</td>
			<td class="separator" style="padding-top: 20px;">:</td>
			<td style="padding-top: 20px; text-align: justify;">
				<span class="bold">{{ $sppd->purpose }}</span> Selama {{ $pdfData['duration'] }} hari dari tanggal
				{{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }} s/d
				{{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}.
			</td>
		</tr>
	</table>

	<div class="footer">
		<div class="signature-wrap">
			<div>Ditetapkan Di : Kendari</div>
			<div>Pada Tanggal : {{ \Carbon\Carbon::parse($sppd->created_at)->translatedFormat('d F Y') }}</div>
			<div style="margin-top: 10px; font-weight: bold; text-transform: uppercase;">
				{{ $pdfData['approver_role'] ?? 'Walikota Kendari' }}</div>
			<div style="height: 80px;">
				{{-- QR Code atau Tanda Tangan --}}
			</div>
			<div class="bold uppercase" style="text-decoration: underline;">
				{{ $pdfData['approver_name'] ?? '................................' }}</div>
			<div>{{ $pdfData['approver_rank'] ?? '' }}</div>
			<div>NIP. {{ $pdfData['approver_nip'] ?? '' }}</div>
		</div>
	</div>

	<div class="clear"></div>

	<div class="tembusan">
		<div class="bold">Tembusan Yth:</div>
		<ol class="ordered-list">
			<li>Kepala Badan Kepegawaian dan Pengembangan SDM Kota Kendari</li>
			<li>Bagian Organisasi dan Pemberdayaan Aparatur Kota Kendari</li>
			<li>Arsip</li>
		</ol>
	</div>
</body>

</html>
