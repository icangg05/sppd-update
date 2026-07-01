<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{{ 'SPT - ' . config('app.name') }}</title>
	<style>
		@page {
			/* margin-bottom diperbesar untuk menyediakan ruang footer tetap
			   (fixed) yang tampil di SETIAP halaman tanpa menimpa isi. */
			margin: 1cm 2.5cm 2.2cm 2.5cm;
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

		.subtitle-left {
			text-align: left;
			margin-left: 179px;
		}

		.subtitle-center {
			text-align: center;
		}

		.content-table {
			width: 100%;
			margin-top: 20px;
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
			/* Jaga blok tanda tangan tidak terpotong antar-halaman. */
			page-break-inside: avoid;
		}

		.signature-wrap {
			float: right;
			width: 270px;
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
		style="{{ $pdfData['is_walikota'] || (!empty($pdfData['letterhead_url']) && \Illuminate\Support\Str::contains($pdfData['letterhead_url'], '/')) ? 'border-bottom: none; padding-bottom: 0;' : '' }}">
		@if ($pdfData['is_walikota'])
			{{-- Saat Walikota penandatangannya, selalu gunakan KOP GARUDA --}}
			<div style="text-align: center; margin-bottom: 10px;">
				<img src="{{ public_path('img/garuda.webp') }}" style="width: 110px; height: auto;">
			</div>
			<div style="margin-top: 22px; font-size: 22pt; font-weight: bold; text-transform: uppercase;">WALIKOTA KENDARI</div>
		@else
			{{-- Jika penandatangan BUKAN Walikota, selalu gunakan KOP DARI INSTANSI --}}
			@if (!empty($pdfData['letterhead_url']) && \Illuminate\Support\Str::contains($pdfData['letterhead_url'], '/'))
				<img src="{{ storage_path('app/public/' . $pdfData['letterhead_url']) }}" style="width: 103%; height: auto;">
			@else
				<div style="font-size: 14pt; font-weight: bold;">PEMERINTAH KOTA KENDARI</div>
				<div style="font-size: 16pt; font-weight: bold; text-transform: uppercase;">
					{{ $sppd->user->department?->getRootDepartment()?->name }}
				</div>
				<div style="font-size: 10pt;">{{ $sppd->user->department?->getRootDepartment()?->address ?? '' }}</div>
			@endif
		@endif
	</div>

	<div class="title">SURAT PERINTAH TUGAS</div>
	<div class="{{ $sppd->document_number ? 'subtitle-center' : 'subtitle-left' }}">
		No : {{ $sppd->document_number ?? '' }}
	</div>

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
		{{-- Daftar peserta digabung (pelaksana utama + pengikut) menjadi satu koleksi
		     agar tiap orang bisa dirender sebagai baris tabel terpisah. Ini membuat
		     Dompdf dapat memecah halaman DI ANTARA orang (bukan menendang seluruh
		     daftar ke halaman berikutnya), sehingga pergantian halaman rapi. --}}
		@php
			$peserta = [];
			$peserta[] = [
				'name'    => $sppd->user->name,
				'rank'    => $sppd->user->rank->name ?? '-',
				'group'   => $sppd->user->rank->group ?? '-',
				'nip'     => $sppd->user->nip,
				'jabatan' => $sppd->user->position->name ?? ($sppd->user->roles->first()->name ?? '-'),
			];
			foreach ($sppd->followers as $follower) {
				$peserta[] = [
					'name'    => $follower->user->name,
					'rank'    => $follower->user->rank->name ?? '-',
					'group'   => $follower->user->rank->group ?? '-',
					'nip'     => $follower->user->nip,
					'jabatan' => $follower->travel_position ?? ($follower->user->position->name ?? ($follower->user->roles->first()->name ?? '-')),
				];
			}
		@endphp
		@foreach ($peserta as $i => $p)
			<tr style="page-break-inside: avoid;">
				<td class="label" style="vertical-align: top; {{ $i === 0 ? 'padding-top: 16px;' : '' }}">{{ $i === 0 ? 'Kepada' : '' }}</td>
				<td class="separator" style="vertical-align: top; {{ $i === 0 ? 'padding-top: 16px;' : '' }}">{{ $i === 0 ? ':' : '' }}</td>
				<td style="padding-left: 2px; {{ $i === 0 ? 'padding-top: 16px;' : '' }}">
					<table style="width: 100%;">
						<tr>
							<td style="padding: 0 0; width: 20px">{{ $i + 1 }}.</td>
							<td style="padding: 0 0; width: 150px">Nama</td>
							<td style="padding: 0 0; width: 10px">:</td>
							<td style="padding: 0 0;">{{ $p['name'] }}</td>
						</tr>
						@if ($p['nip'])
							<tr>
								<td style="padding: 0 0;"></td>
								<td style="padding: 0 0;">Pangkat/Golongan</td>
								<td style="padding: 0 0;">:</td>
								<td style="padding: 0 0;">{{ $p['rank'] }}, Gol. {{ $p['group'] }}</td>
							</tr>
							<tr>
								<td style="padding: 0 0;"></td>
								<td style="padding: 0 0;">NIP</td>
								<td style="padding: 0 0;">:</td>
								<td style="padding: 0 0;">{{ $p['nip'] }}</td>
							</tr>
						@endif
						<tr>
							<td style="padding: 0 0;"></td>
							<td style="padding: 0 0;">Jabatan</td>
							<td style="padding: 0 0;">:</td>
							<td style="padding: 0 0; text-transform: uppercase;">{{ $p['jabatan'] }}</td>
						</tr>
					</table>
				</td>
			</tr>
		@endforeach
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
			@if ($sppd->user->department?->getRootDepartment()?->type !== \App\Enums\DepartmentType::DPRD)
				<div>Ditetapkan di Kendari</div>
			@endif
			<div>
				{{ $sppd->user->department?->getRootDepartment()?->type !== \App\Enums\DepartmentType::DPRD ? 'Pada Tanggal :' : 'Kendari,' }}
				{{ $sppd->spt_date->translatedFormat('d F Y') }}
			</div>
			<div style="margin-top: 10px; text-transform: uppercase;">
				{{ $pdfData['approver_role'] ?? 'Walikota Kendari' }}
			</div>
			@if ($pdfData['is_approved'] && $pdfData['qr_image'])
				<div style="margin-top: 11px;">
					<img src="{{ $pdfData['qr_image'] }}">
				</div>
			@else
				<div style="height: 70px;"></div>
			@endif
			<p style="margin-top: 3px; line-height: 1.8;">
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

	@php
		$isDprdDepartment = $sppd->user->department?->getRootDepartment()?->type === \App\Enums\DepartmentType::DPRD;
		$isAnggotaDprd = $sppd->user->hasRole(['anggota_dprd', 'pimpinan_dprd']);
	@endphp
	@if (!($isDprdDepartment && $isAnggotaDprd))
		<div class="tembusan">
			<div>Tembusan Yth:</div>
			<ol class="ordered-list">
				<li>Kepala Badan Kepegawaian dan Pengembangan SDM Kota Kendari</li>
				<li>Bagian Organisasi dan Pemberdayaan Aparatur Kota Kendari</li>
			</ol>
		</div>
	@endif

	{{-- Footer dokumen: gaya lebih halus (abu-abu) khas footer dokumen,
	     position fixed => diulang Dompdf di SETIAP halaman. --}}
	<footer
		style="font-size: 8pt; color: #6b7280; position: fixed; bottom: -65px; left: 0; right: 0; font-family: Arial, Helvetica, sans-serif">
		<div style="font-style: italic; margin-bottom: 6px;">
			Tidak Menerima Gratifikasi Dalam Bentuk Apapun Selama Pelaksanaan Tugas
		</div>
		<div style="border-top: 1px solid #d1d5db; margin: 4px 0"></div>
		<div style="font-style: italic; margin-top: 5px; text-align: right;">
			Dokumen ini ditandatangani secara elektronik menggunakan Layanan BSrE
		</div>
	</footer>
</body>

</html>
