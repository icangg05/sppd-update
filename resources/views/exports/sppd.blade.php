@php
	// Membuat closure untuk mengubah angka menjadi romawi
	$toRoman = function ($number) {
	    $map = [
	        'M' => 1000,
	        'CM' => 900,
	        'D' => 500,
	        'CD' => 400,
	        'C' => 100,
	        'XC' => 90,
	        'L' => 50,
	        'XL' => 40,
	        'X' => 10,
	        'IX' => 9,
	        'V' => 5,
	        'IV' => 4,
	        'I' => 1,
	    ];
	    $returnValue = '';
	    while ($number > 0) {
	        foreach ($map as $roman => $int) {
	            if ($number >= $int) {
	                $number -= $int;
	                $returnValue .= $roman;
	                break;
	            }
	        }
	    }
	    return $returnValue;
	};
@endphp

<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{{ config('app.name') }}</title>
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
			padding-top: 8px;
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
			font-size: 7pt;
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
			width: 50%;
			padding: 1px;
			vertical-align: top;
			font-size: 7pt;
		}

		.table-right tr,
		.table-right td {
			border: none;
			padding: 0;
		}

		.bline {
			border: 1px solid red !important;
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
					<tr>
						<td style="padding: 0; width: 60px;">Lembar Ke</td>
						<td style="padding: 0;">:</td>
						<td style="padding: 0;">I, II, III, IV</td>
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
							<td style="padding: 30px 0;" colspan="2"></td>
						</tr>
						<tr>
							<td style="padding: 3px 0 0 0; font-weight: bold; text-decoration: underline;"
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
				<!-- Baris I -->
				<tr>
					<td></td>
					<td>
						<table class="table-right">
							<tr>
								<td style="width: 14px;">I.</td>
								<td style="width: 100%;">Berangkat dari <br>(Tempat Kedudukan)</td>
								<td>: Kendari</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Pada Tanggal</td>
								<td>: {{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }}</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="text-transform: uppercase; padding: 3px 0; font-weight: bold;">
									{{ $pdfData['approver_role'] }}</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="padding: 25px 0;"></td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="text-decoration: underline; font-weight: bold;">
									{{ $pdfData['approver_name'] }}</td>
							</tr>
							@if ($pdfData['approver_nip'])
								<tr>
									<td style="width: 14px;"></td>
									<td colspan="2">
										NIP. {{ $pdfData['approver_nip'] }}</td>
								</tr>
							@endif
						</table>
					</td>
				</tr>

				<!-- Baris II -->
				<tr>
					<td style="height: 100px; position: relative;">
						<table class="table-right">
							<tr>
								<td style="width: 14px;">II.</td>
								<td style="width: 90px;">Tiba Di</td>
								<td style="text-transform: uppercase;">
									: {{ $sppd->destinations->first()->regency->name ?? '' }}
								</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Pada Tanggal</td>
								<td>: {{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }}</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Jabatan</td>
								<td>:</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2">
									<div style="width: 75%; height: 0.5px; background: black; position: absolute; bottom: 7px;"></div>
								</td>
							</tr>
						</table>
					</td>
					<td style="height: 100px; position: relative;">
						<table class="table-right">
							<tr>
								<td style="width: 14px;"></td>
								<td style="width: 90px;">Berangkat dari</td>
								<td style="text-transform: uppercase;">: {{ $sppd->destinations->first()->regency->name ?? '' }}</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Ke</td>
								<td>:</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Pada Tanggal</td>
								<td>:</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Jabatan</td>
								<td>:</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2">
									<div style="width: 75%; height: 0.5px; background: black; position: absolute; bottom: 7px;"></div>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<!-- Baris III, IV, V -->
				@for ($i = 3; $i <= 5; $i++)
					<tr>
						<td style="height: 100px; position: relative;">
							<table class="table-right">
								<tr>
									<td style="width: 14px;">{{ $toRoman($i) }}.</td>
									<td style="width: 90px;">Tiba Di</td>
									<td style="text-transform: uppercase;">:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td>Pada Tanggal</td>
									<td>:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td>Jabatan</td>
									<td>:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td colspan="2">
										<div style="width: 75%; height: 0.5px; background: black; position: absolute; bottom: 7px;"></div>
									</td>
								</tr>
							</table>
						</td>
						<td style="height: 100px; position: relative;">
							<table class="table-right">
								<tr>
									<td style="width: 14px;"></td>
									<td style="width: 90px;">Berangkat dari</td>
									<td style="text-transform: uppercase;">:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td>Ke</td>
									<td>:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td>Pada Tanggal</td>
									<td>:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td>Jabatan</td>
									<td>:</td>
								</tr>
								<tr>
									<td style="width: 14px;"></td>
									<td colspan="2">
										<div style="width: 75%; height: 0.5px; background: black; position: absolute; bottom: 7px;"></div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				@endfor

				<!-- Baris VI -->
				<tr>
					<td>
						<table class="table-right">
							<tr>
								<td style="width: 14px;">VI.</td>
								<td style="width: 100%;">Tiba Di <br>(Tempat Kedudukan)</td>
								<td>: Kendari</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td>Pada Tanggal</td>
								<td>: {{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="padding: 15px 0 0 0;">
									Pejabat yang memberi perintah<br>
									<span style="text-transform: uppercase; font-weight: bold;">
										{{ $pdfData['approver_role'] }}
									</span>
								</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="padding: 25px 0;"></td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="text-decoration: underline; font-weight: bold;">
									{{ $pdfData['approver_name'] }}</td>
							</tr>
							@if ($pdfData['approver_nip'])
								<tr>
									<td style="width: 14px;"></td>
									<td colspan="2">
										NIP. {{ $pdfData['approver_nip'] }}</td>
								</tr>
							@endif
						</table>
					</td>
					<td>
						<table class="table-right">
							<tr>
								<td style="width: 100%; text-align: justify;" colspan="2">
									Telah diperiksa dengan keterangan bahwa perjalanan tersebut di atas telah benar dilakukan atas perintahnya
									semata-mata untuk kepentingan jabatan dalam waktu yang sesingkat-singkatnya.
								</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="padding: 3px 0;">
									Pejabat yang memberi perintah<br>
									<span style="text-transform: uppercase; font-weight: bold;">
										{{ $pdfData['approver_role'] }}
									</span>
								</td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="padding: 25px 0;"></td>
							</tr>
							<tr>
								<td style="width: 14px;"></td>
								<td colspan="2" style="text-decoration: underline; font-weight: bold;">
									{{ $pdfData['approver_name'] }}</td>
							</tr>
							@if ($pdfData['approver_nip'])
								<tr>
									<td style="width: 14px;"></td>
									<td colspan="2">
										NIP. {{ $pdfData['approver_nip'] }}</td>
								</tr>
							@endif
						</table>
					</td>
				</tr>

				<!-- Baris VII -->
				<tr>
					<td colspan="2">
						<table class="table-right">
							<tr>
								<td style="width: 14px;">VII.</td>
								<td style="width: 100%;">Keterangan Lain-lain</td>
							</tr>
						</table>
					</td>
				</tr>

				<!-- Baris VIII -->
				<tr>
					<td colspan="2">
						<table class="table-right">
							<tr>
								<td style="width: 14px;">VIII.</td>
								<td style="width: 100%;">PERHATIAN</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="table-right">
							<tr>
								<td style="width: 100%; text-align: justify;">
									Pejabat yang berwenang memberi SPPD pegawai yang melakukan
									Perjalanan Dinas, para
									pejabat yang mengesahkan tanggal berangkat/tiba, serta bendaharawan bertanggung jawab berdasarkan
									peraturan-peraturan Keuangan Negara, apabila Negara menderita rugi akibat kesalahan, kelalaian dan kealpaan
									(Lampiran SK. Menteri Keuangan tanggal 30-4-1974 Nomor B-296/MK/I/1974).</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
		<div class="clear"></div>
	</div>

	<footer style="font-size: 7pt;">
		<div style="position: fixed; bottom: 5px; width: 100%;">
			<div style="width: 100%; border-top: 1px solid black; padding: 0 0 5px 0;"></div>

			<table style="width: 100%; font-style: italic;">
				<tr>
					<td>
						<div style="width: 100%; float: left;">
							Tidak Menerima Gratifikasi Dalam Bentuk Apapun Selama Pelaksanaan Tugas
						</div>
					</td>
					<td>
						<div style="width: 100%; float: right; text-align: right;">
							Dokumen ini ditandatangani secara elektronik menggunakan Layanan BSrE
						</div>
					</td>
				</tr>
			</table>
		</div>
	</footer>
</body>

</html>
