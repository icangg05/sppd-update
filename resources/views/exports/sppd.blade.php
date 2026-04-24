<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style>
		@page {
			margin: 0.7cm 1cm;
			size: 33cm 24.14cm;
		}

		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 9pt;
			line-height: 1.2;
			color: #000;
		}

		.container {
			width: 100%;
			clear: both;
		}

		.left-column {
			width: 54%;
			float: left;
		}

		.right-column {
			padding-top: 25px;
			width: 44%;
			float: right;
			padding-left: 1%;
		}

		.header-table {
			width: 100%;
		}

		.logo {
			width: 45px;
		}

		.title {
			text-align: center;
			font-weight: bold;
			font-size: 9pt;
			margin: 8px 0;
			margin-bottom: 14px;
			text-decoration: underline;
		}

		.main-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 10px;
			font-size: 7pt;
		}

		.main-table th,
		.main-table td {
			border: 1px solid #000;
			padding: 1px 4px;
			vertical-align: top;
		}

		.no-col {
			width: 20px;
			text-align: center;
		}

		.label-col {
			width: 180px;
		}

		.signature-section {
			width: 100%;
			margin-top: 20px;
			font-size: 7pt
		}

		.sig-box {
			float: right;
			width: 307px;
			text-align: left;
		}

		.clear {
			clear: both;
		}

		/* Back Page Styles */
		.back-table {
			width: 100%;
			border-collapse: collapse;
		}

		.back-table td {
			border: 1px solid #000;
			height: 80px;
			width: 50%;
			padding: 4px;
			vertical-align: top;
			font-size: 7pt;
		}

		.bline {
			border: 1px solid red;
		}
	</style>
</head>

<body>
	<div class="container">
		<!-- FRONT PAGE (LEFT COLUMN) -->
		<div class="left-column">
			<div class="header-container"
				style="{{ $user->department->letterhead && \Illuminate\Support\Str::contains($user->department->letterhead, '/') ? 'border-bottom: none; margin-bottom: 0;' : 'border-bottom: 2px solid #000; padding-bottom: 3px; margin-bottom: 8px;' }}">
				@if ($user->department->letterhead && \Illuminate\Support\Str::contains($user->department->letterhead, '/'))
					<img src="{{ storage_path('app/public/' . $user->department->letterhead) }}" style="width: 103%; height: auto;">
				@else
					<table class="header-table" style="border-bottom: none; margin-bottom: 0;">
						<tr>
							<td class="logo">
								<img src="{{ public_path('images/logo-pemkot.png') }}" style="width: 100%;">
							</td>
							<td style="text-align: center;">
								<div style="font-size: 14pt; font-weight: bold;">PEMERINTAH KOTA KENDARI</div>
								<div style="font-size: 20pt; font-weight: bold; text-transform: uppercase;">{{ $user->department->name }}</div>
								<div style="font-size: 7pt;">{{ $user->department->address ?? 'Jl. Drs. H. Abd Silondae No. 8 Kendari' }}</div>
							</td>
						</tr>
					</table>
				@endif
			</div>

			<div style="float: right; width: 150px; font-size: 7pt;">
				<table style="width: 100%;">
					<tr>
						<td style="padding: 0 0; width: 60px;">Lampiran</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;"></td>
					</tr>
					<tr class="bline">
						<td style="padding: 0 0; width: 60px;">Lembar Ke</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;">I, II, III, IV</td>
					</tr>
					<tr>
						<td style="padding: 0 0; width: 60px;">Kode No</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;"></td>
					</tr>
					<tr>
						<td style="padding: 0 0; width: 60px;">Nomor</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;">{{ $sppd->document_number }}</td>
					</tr>
				</table>
			</div>
			<div class="clear"></div>

			<div class="title">SURAT PERINTAH PERJALANAN DINAS (SPPD)</div>

			<table class="main-table">
				<tr>
					<td style="width: 1%; text-align: center;">1.</td>
					<td style="width: 46%">Pejabat berwenang yang memberi perintah</td>
					<td style="width: 54%">{{ $pdfData['approver_role'] ?? 'Kepala Dinas' }}</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">2.</td>
					<td>Nama Pegawai yang diperintahkan</td>
					<td>{{ $user->name }}</td>
				</tr>
				<tr>
					<td rowspan="3" style="width: 1%; text-align: center; vertical-align: top;">3.</td>
					<td style="width: 46%; border-bottom: none;">
						a. &nbsp;Pangkat dan Golongan ruang gaji<br>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;menurut PP No.30 Tahun 2015
					</td>
					<td style="width: 54%; border-bottom: none;">a. &nbsp;{{ $user->rank->name ?? '-' }} -
						{{ $user->rank->group ?? '-' }}
					</td>
				</tr>
				<tr>
					<td style="border-top: none; border-bottom: none;">b. &nbsp;Jabatan / Instansi</td>
					<td style="border-top: none; border-bottom: none;">
						b. <span style="text-transform: uppercase">
							{{ $user->position_name ?? ($user->position->name ?? ($user->roles->first()->name ?? '-')) }}</span>
					</td>
				</tr>
				<tr>
					<td style="border-top: none;">c. &nbsp;Tingkat Biaya Perjalanan Dinas</td>
					<td style="border-top: none;">c. &nbsp;-</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">4.</td>
					<td>Maksud Perjalanan Dinas</td>
					<td style="text-align: justify;">{{ $sppd->purpose }}</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">5.</td>
					<td>Alat angkutan yang dipergunakan</td>
					<td>{{ $sppd->transport_name }}</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">6.</td>
					<td>
						a. &nbsp;Tempat Berangkat<br>
						b. &nbsp;Tempat Tujuan
					</td>
					<td>
						a. &nbsp;{{ $sppd->departure_place ?? 'Kendari' }}<br>
						b.&nbsp;@foreach ($sppd->destinations as $dest)
							{{ $dest->regency->name ?? '' }}@if (!$loop->last)
								,
							@endif
						@endforeach
					</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">7.</td>
					<td>
						a. &nbsp;Lamanya Perjalanan Dinas<br>
						b. &nbsp;Tanggal berangkat<br>
						c. &nbsp;Tanggal harus kembali
					</td>
					<td>
						a. &nbsp;{{ $pdfData['duration'] }} Hari<br>
						b. &nbsp;{{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }}<br>
						c. &nbsp;{{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}
					</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">8.</td>
					<td>Pengikut</td>
					<td>
						Keterangan
					</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">9.</td>
					<td>
						Pembebanan Anggaran<br>
						a. &nbsp;Instansi<br>
						b. &nbsp;Mata Anggaran
					</td>
					<td>
						<br>
						a. &nbsp;{{ $user->department->name }}<br>
						b. &nbsp;{{ $sppd->budget->account_code ?? '-' }}<br>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $sppd->budget->source }}
					</td>
				</tr>
				<tr>
					<td style="width: 1%; text-align: center;">10.</td>
					<td>Keterangan lain-lain</td>
					<td>-</td>
				</tr>
			</table>

			<div class="signature-section">
				<div class="sig-box">
					<table>
						<tr>
							<td style="padding: 0.5px 0; width: 120px;">Dikeluarkan di</td>
							<td style="padding: 0.5px 0;">: Kendari</td>
						</tr>
						<tr>
							<td style="padding: 0.5px 0; width: 120px;">Tanggal</td>
							<td style="padding: 0.5px 0;">: {{ $sppd->sppd_date->translatedFormat('d F Y') }}</td>
						</tr>
						<tr>
							<td style="padding: 3px 0; font-weight: bold; text-transform: uppercase;" colspan="2">
								{{ $pdfData['approver_role'] }}
							</td>
						</tr>
						<tr>
							<td style="padding: 35px 0;" colspan="2"></td>
						</tr>
						<tr>
							<td style="padding: 3px 0; font-weight: bold; text-transform: uppercase; text-decoration: underline;"
								colspan="2">
								{{ $pdfData['approver_name'] }}
							</td>
						</tr>
						@if ($pdfData['approver_nip'])
							<tr>
								<td style="padding: 0.5px 0;" colspan="2">
									{{ $pdfData['approver_rank'] }}, Gol. {{ $pdfData['approver_group'] }}
								</td>
							</tr>
							<tr>
								<td style="padding: 0.5px 0;" colspan="2">
									NIP. {{ $pdfData['approver_nip'] }}
								</td>
							</tr>
						@endif
					</table>
				</div>
			</div>
		</div>

		<!-- BACK PAGE (RIGHT COLUMN) -->
		<div class="right-column">
			<table class="back-table">
				<tr>
					<td></td>
					<td>
						I. Berangkat dari : Kendari<br>
						Pada Tanggal : {{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d-m-Y') }}<br>
						KEPALA DINAS<br><br><br>
						<b>{{ $pdfData['approver_name'] }}</b><br>
						NIP. {{ $pdfData['approver_nip'] }}
					</td>
				</tr>
				<tr>
					<td>
						II. Tiba di : {{ $sppd->destinations->first()->regency->name ?? '........' }}<br>
						Pada Tanggal : ....................
					</td>
					<td>
						Berangkat dari : {{ $sppd->destinations->first()->regency->name ?? '........' }}<br>
						Ke : ....................<br>
						Pada Tanggal : ....................
					</td>
				</tr>
				<tr>
					<td>III. Tiba di : ....................</td>
					<td>Berangkat dari : ....................</td>
				</tr>
				<tr>
					<td>IV. Tiba di : ....................</td>
					<td>Berangkat dari : ....................</td>
				</tr>
				<tr>
					<td>V. Tiba di : ....................</td>
					<td>Berangkat dari : ....................</td>
				</tr>
				<tr>
					<td>
						VI. Tiba di : Kendari<br>
						Pada Tanggal : {{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d-m-Y') }}<br><br>
						Pejabat yang memberi perintah<br>
						KEPALA DINAS<br><br><br>
						<b>{{ $pdfData['approver_name'] }}</b>
					</td>
					<td>
						Telah diperiksa dengan keterangan bahwa perjalanan tersebut di atas benar dilakukan...<br><br>
						Pejabat yang memberi perintah<br>
						KEPALA DINAS<br><br><br>
						<b>{{ $pdfData['approver_name'] }}</b>
					</td>
				</tr>
			</table>
		</div>
		<div class="clear"></div>
	</div>
</body>

</html>
