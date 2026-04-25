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

		.title {
			text-align: center;
			text-decoration: underline;
			font-weight: bold;
			font-size: 16pt;
			text-transform: uppercase;
		}

		.subtitle {
			text-align: left;
			margin-left: 179px;
		}

		.content-table {
			width: 100%;
			margin-top: 35px;
			vertical-align: top;
		}

		.content-table td {
			vertical-align: top;
			padding: 0;
		}

		.label {
			width: 80px;
		}

		.separator {
			width: 10px;
			text-align: center;
		}

		.ordered-list {
			margin: 0;
			padding-left: 20px;
		}

		.footer {
			margin-top: 10px;
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

		footer {
			position: fixed;
			bottom: -20px;
			left: 0;
			right: 0;
			text-align: center;
			font-size: 9pt;
			font-style: italic;
		}

		.bline {
			border: 1px solid red !important;
		}
	</style>
</head>

<body>
	<div class="header"
		style="{{ $pdfData['is_walikota'] || ($sppd->user->department->letterhead && \Illuminate\Support\Str::contains($sppd->user->department->letterhead, '/')) ? 'border-bottom: none; padding-bottom: 0;' : '' }}">
		@if ($pdfData['is_walikota'])
			<div style="text-align: center; margin-bottom: 10px;">
				<img src="{{ public_path('img/garuda.png') }}" style="width: 110px; height: auto;">
			</div>
			<div style="margin-top: 22px; font-size: 22pt; font-weight: bold; text-transform: uppercase;">WALIKOTA KENDARI</div>
		@elseif ($sppd->user->department->letterhead && \Illuminate\Support\Str::contains($sppd->user->department->letterhead, '/'))
			<img src="{{ storage_path('app/public/' . $sppd->user->department->letterhead) }}" style="width: 103%; height: auto;">
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

	<div class="title">SURAT PERINTAH TUGAS</div>
	<div class="subtitle">No : {{ $sppd->document_number ?? '' }}</div>

	<table class="content-table">
		<tr>
			<td class="label">Dari</td>
			<td class="separator">:</td>
			<td style="padding-left: 5px;">
				{{ $pdfData['approver_role'] ?? 'Walikota Kendari' }}
			</td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: center; padding: 15px 0; font-weight: bold; font-size: 14pt">MEMERINTAHKAN</td>
		</tr>
		<tr>
			<td class="label">Kepada</td>
			<td class="separator">:</td>
			<td style="padding-left: 2px;">
				<table style="width: 100%; margin-top: 16px">
					{{-- Pelaksana Utama --}}
					<tr>
						<td style="padding: 0 0; width: 20px">1.</td>
						<td style="padding: 0 0; width: 150px">Nama</td>
						<td style="padding: 0 0; width: 10px">:</td>
						<td style="padding: 0 0;">{{ $sppd->user->name }}</td>
					</tr>
					<tr>
						<td style="padding: 0 0;"></td>
						<td style="padding: 0 0;">Pangkat/Golongan</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;">{{ $sppd->user->rank->name ?? '-' }}, Gol. {{ $sppd->user->rank->group ?? '-' }}</td>
					</tr>
					<tr>
						<td style="padding: 0 0;"></td>
						<td style="padding: 0 0;">NIP</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0;">{{ $sppd->user->nip ?? '-' }}</td>
					</tr>
					<tr>
						<td style="padding: 0 0;"></td>
						<td style="padding: 0 0;">Jabatan</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0; text-transform: uppercase;">
							{{ $sppd->user->position_name ?? ($sppd->user->position->name ?? ($sppd->user->roles->first()->name ?? '-')) }}
						</td>
					</tr>

					{{-- Pengikut --}}
					@foreach ($sppd->followers as $index => $follower)
						<tr>
							<td style="padding: 0 0;">{{ $index + 2 }}.</td>
							<td style="padding: 0 0;">Nama</td>
							<td style="padding: 0 0;">:</td>
							<td style="padding: 0 0;">{{ $follower->user->name }}</td>
						</tr>
						<tr>
							<td style="padding: 0 0;"></td>
							<td style="padding: 0 0;">Pangkat/Gol</td>
							<td style="padding: 0 0;">:</td>
							<td style="padding: 0 0;">{{ $follower->user->rank->name ?? '-' }}, Gol.
								{{ $follower->user->rank->group ?? '-' }}</td>
						</tr>
						<tr>
							<td style="padding: 0 0;"></td>
							<td style="padding: 0 0;">NIP</td>
							<td style="padding: 0 0;">:</td>
							<td style="padding: 0 0;">{{ $follower->user->nip ?? '-' }}</td>
						</tr>
						<tr>
							<td style="padding: 0 0;"></td>
							<td style="padding: 0 0;">Jabatan</td>
							<td style="padding: 0 0;">:</td>
							<td style="padding: 0 0; text-transform: uppercase;">
								{{ $follower->user->position_name ?? ($follower->user->position->name ?? ($follower->user->roles->first()->name ?? '-')) }}
							</td>
						</tr>
					@endforeach
				</table>
			</td>
		</tr>
		<tr>
			<td class="label" style="padding-top: 20px;">Untuk</td>
			<td class="separator" style="padding-top: 20px;">:</td>
			<td style="padding-top: 20px; padding-left: 5px; text-align: justify;">
				<span class="bold">{{ $sppd->purpose }}</span> Selama {{ $pdfData['duration'] }} hari dari tanggal
				{{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }} s/d
				{{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}.
			</td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: justify; padding-top: 4px;">
				<p>
					Demikian Surat Tugas ini diberikan kepada yang bersangkutan untuk dilaksanakan dengan penuh rasa tanggung jawab.
				</p>
			</td>
		</tr>
	</table>

	<div class="footer">
		<div class="signature-wrap">
			<div>Ditetapkan di Kendari</div>
			<div>Pada Tanggal : {{ $sppd->spt_date->translatedFormat('d F Y') }}</div>
			<div style="margin-top: 10px; font-weight: bold; text-transform: uppercase;">
				{{ $pdfData['approver_role'] ?? 'Walikota Kendari' }}
			</div>
			@if ($pdfData['is_approved'] && $pdfData['qr_image'])
				<div style="margin-top: 8px;">
					<img src="{{ $pdfData['qr_image'] }}" style="width: 80px; height: 80px;">
				</div>
			@else
				<div style="height: 70px;"></div>
			@endif
			<p style="margin-top: 5px; line-height: 1.8;">
				<span style="font-weight: bold; text-decoration: underline;">
					{{ $pdfData['approver_name'] }}
				</span>
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

	<div class="clear"></div>

	<div class="tembusan">
		<div>Tembusan Yth:</div>
		<ol class="ordered-list">
			<li>Kepala Badan Kepegawaian dan Pengembangan SDM Kota Kendari</li>
			<li>Bagian Organisasi dan Pemberdayaan Aparatur Kota Kendari</li>
		</ol>
	</div>

	<footer style="position: absolute; bottom: -10px; left: 0; right: 0; font-family: Arial, Helvetica, sans-serif">
		<div style="font-style: italic; margin-bottom: 10px;">Tidak Menerima Gratifikasi Dalam Bentuk Apapun Selama
			Pelaksanaan Tugas</div>
		<div style="border-top: 1px solid #000; margin: 5px 0"></div>
		<div style="text-align: right">Dokumen ini ditandatangani secara elektronik menggunakan Layanan BSrE</div>
	</footer>
</body>

</html>
