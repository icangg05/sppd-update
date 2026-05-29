<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{{ 'SPT - ' . config('app.name') }}</title>
	<style>
		@page {
			margin: 1cm 2.5cm;
			size: 21.5cm 33cm;
		}

		body {
			font-family: 'Times New Roman', Times, serif;
			line-height: 1.5;
			font-size: 10pt;
			color: #000;
		}

		table {
			border-collapse: collapse;
		}

		.title {
			text-align: center;
			text-decoration: underline;
			font-weight: bold;
			font-size: 16pt;
			text-transform: uppercase;
		}

		.subtitle-left {
			text-align: left;
			margin-left: 179px;
		}

		.data-table {
			width: 100%;
			border: 1px solid #000;
			font-size: 9pt;
		}

		.data-table th,
		.data-table td {
			border: 1px solid #000;
			padding: 2.5px 8px;
		}

		.data-table th {
			background: #f5f5f5;
			font-weight: bold;
			text-align: center;
			font-size: 10pt;
		}

		.footer-note {
			position: absolute;
			bottom: -10px;
			left: 0;
			right: 0;
			font-family: Arial, Helvetica, sans-serif;
		}
	</style>
</head>

<body>
	@php
		$dasar = [
		    'Perda Kota Kendari No. 09 Tahun 2025 tentang APBD Kota Kendari tahun 2026;',
		    'Peraturan Tata Tertib Dewan Perwakilan Rakyat Daerah Kota Kendari;',
		    'Program Kerja DPRD Kota Kendari tahun 2026;',
		    'DPA-SKPD Sekretariat DPRD Kota Kendari;',
		];
	@endphp

	<div style="text-align: center; margin-bottom: 16px;">
		@if ($pdfData['letterhead_url'])
			<img src="{{ storage_path('app/public/' . $pdfData['letterhead_url']) }}" style="width: 103%; height: auto;">
		@else
			<img src="{{ public_path('img/SEKRETARIAT_DPRD2.jpg') }}" style="width: 103%; height: auto;">
		@endif
	</div>

	<div class="title">SURAT PERINTAH TUGAS</div>
	<div class="subtitle-left">NOMOR : {{ $sppd->document_number ?? '' }}</div>

	<div style="margin-top: 15px; line-height: 1.7;">
		<ol style="padding-left: 1.2rem; margin: 0;">

			<li style="margin-bottom: 15px;">
				Dasar :<br />
				<ol style="list-style: lower-alpha; padding-left: 1.2rem; margin: 0;">
					@foreach ($dasar as $item)
						<li>{{ $item }}</li>
					@endforeach
				</ol>
				<p style="margin-top: 5px;">
					Menugaskan Kepada Anggota DPRD Kota Kendari yang tercantum namanya dibawah ini:
				</p>
				<table class="data-table" style="margin-top: 10px; margin-bottom: 10px;">
					<thead>
						<tr>
							<th style="width: 5px;">No.</th>
							<th style="width: 200px;">Nama</th>
							<th>Jabatan</th>
							<th style="width: 70px;">Ket</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="text-align: center;">1.</td>
							<td>{{ $sppd->user->name }}</td>
							<td style="text-transform: uppercase;">
								{{ $sppd->user->isDprdMember() ? $sppd->user->dprd_jabatan ?? 'ANGGOTA DPRD' : $sppd->user->position->name ?? ($sppd->user->roles->first()->name ?? '-') }}
							</td>
							<td></td>
						</tr>
						@foreach ($sppd->followers as $index => $follower)
							<tr>
								<td style="text-align: center;">{{ $index + 2 }}.</td>
								<td>{{ $follower->user->name }}</td>
								<td style="text-transform: uppercase;">
									{{ $follower->user->isDprdMember() ? $follower->user->dprd_jabatan ?? 'ANGGOTA DPRD' : $follower->travel_position ?? ($follower->user->position->name ?? ($follower->user->roles->first()->name ?? '-')) }}
								</td>
								<td></td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</li>

			<li style="margin-bottom: 15px;">
				Tujuan Perintah Tugas :<br />
				{{ $sppd->purpose }}
			</li>

			<li style="margin-bottom: 15px;">
				Waktu dan Tempat Pelaksanaan :<br />
				<table>
					<tr>
						<td style="width: 90px;">Hari/Tanggal</td>
						<td style="padding: 0 10px;">:</td>
						<td>Selama {{ $pdfData['duration'] }} hari dari tanggal
							{{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }} s/d
							{{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}.</td>
					</tr>
					<tr>
						<td style="width: 90px;">Tempat</td>
						<td style="padding: 0 10px;">:</td>
						<td>
							@foreach ($sppd->destinations as $dest)
								{{ $dest->regency->name }}{{ !$loop->last ? ', ' : '' }}
							@endforeach
						</td>
					</tr>
				</table>
			</li>

		</ol>
	</div>

	<div style="line-height: 1.7;">
		Demikian Surat Perintah Tugas ini diberikan untuk dilaksanakan dengan penuh rasa tanggung jawab dan
		apabila Surat Perintah Tugas Ini tidak dijalankan sesuai aturan Perundang-Undangan yang berlaku, maka yang
		bersangkutan dan/atau penerima Surat Perintah Tugas ini yang akan bertanggung jawab.
	</div>

	<div style="margin-top: 25px;">
		<div style="float: right; width: 270px; text-align: left;">
			<div>Kendari,
				{{ $sppd->spt_date ? $sppd->spt_date->translatedFormat('d F Y') : \Carbon\Carbon::now()->translatedFormat('d F Y') }}
			</div>
			<div style="text-transform: uppercase;">{{ $pdfData['approver_role'] ?? 'KETUA DPRD KOTA KENDARI' }}</div>
			@if ($pdfData['is_approved'] && $pdfData['qr_image'])
				<div style="margin-top: 11px;">
					<img src="{{ $pdfData['qr_image'] }}" style="width: 65px;">
				</div>
			@else
				<div style="height: 70px;"></div>
			@endif
			<p style="margin-top: 3px; line-height: 1.8;">
				<span style="font-weight: bold; text-decoration: underline;">{{ $pdfData['approver_name'] }}</span>
				@if ($pdfData['approver_nip'])
					<br>
					<span>
						{{ $pdfData['approver_rank'] ?? '-' }}, Gol. {{ $pdfData['approver_group'] ?? '-' }} <br>
					</span>
					<span>
						NIP. {{ $pdfData['approver_nip'] }}
					</span>
				@endif
			</p>
		</div>
	</div>

	<div style="clear: both;"></div>

	<div style="margin-top: 25px; font-size: 10pt;">
		<div>Tembusan Yth:</div>
		<ol style="margin: 0; padding-left: 20px;">
			<li>Walikota Kendari</li>
			<li>Arsip</li>
		</ol>
	</div>

	<footer class="footer-note">
		<div style="font-style: italic; margin-bottom: 10px; text-align: center;">
			Tidak Menerima Gratifikasi Dalam Bentuk Apapun Selama Pelaksanaan Tugas
		</div>
		<div style="border-top: 1px solid #000; margin: 5px 0;"></div>
		<div style="font-style: italic; margin-top: 8px; text-align: right;">
			Dokumen ini ditandatangani secara elektronik menggunakan Layanan BSrE
		</div>
	</footer>

</body>

</html>
