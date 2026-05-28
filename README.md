SPPD Update Kota Kendari => Laravel 13

koreksi yg belum:

1. pada detail halaman perjalanan, detail spt dan detail sppd tidak perlu tampilkan tombol download
2. pada file spt yang di tte masih muncul qrcode kecil yang posisi bukan diatas nama si penandatangan. hilangkan qrcode bawaan tersebut. spt harusnya hanya satu qrcode saja sedangkan saat ini hasilnya dua.

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
					@if ($sppd->user->nip)
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
					@endif
					<tr>
						<td style="padding: 0 0;"></td>
						<td style="padding: 0 0;">Jabatan</td>
						<td style="padding: 0 0;">:</td>
						<td style="padding: 0 0; text-transform: uppercase;">
							{{ $sppd->user->position->name ?? ($sppd->user->roles->first()->name ?? '-') }}
						</td>
					</tr>
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
