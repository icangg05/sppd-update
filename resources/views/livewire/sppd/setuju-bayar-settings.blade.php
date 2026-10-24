<div>
	{{-- Ringkasan penandatangan Setuju Bayar yang berlaku untuk dokumen SPPD ini. --}}
	<div class="dash-enter flex h-full flex-col gap-3 rounded border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center">
		<div class="flex min-w-0 flex-1 items-center gap-3">
			<span class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
				<i class="fa-solid fa-signature"></i>
			</span>
			<div class="min-w-0 leading-tight">
				{{-- Cakupan: diatur per instansi, berlaku untuk semua SPPD instansi itu. --}}
				<div class="flex flex-wrap items-center gap-x-2 gap-y-1">
					<p class="text-sm font-bold text-slate-800">Penandatangan Setuju Bayar</p>
					<x-ui.badge color="slate"><i class="fa-solid fa-building mr-1 text-[9px]"></i> Berlaku semua SPPD</x-ui.badge>
				</div>
				<p class="mt-0.5 truncate text-xs text-slate-500">
					@if ($current['name'])
						<span class="font-semibold text-slate-700">{{ $current['name'] }}</span> · {{ $current['role'] }}
						@if ($current['isDefault'])
							<span class="text-slate-400">(bawaan: Kepala OPD)</span>
						@endif
					@else
						<span class="text-amber-700">Belum ada penandatangan. Kepala OPD instansi ini belum terdaftar.</span>
					@endif
				</p>
			</div>
		</div>
		@if ($canManage)
			<x-ui.button variant="secondary" size="sm" wire:click="open" class="shrink-0 self-start sm:self-auto">
				<x-slot:icon><i class="fa-solid fa-pen-to-square text-xs"></i></x-slot:icon>
				Atur
			</x-ui.button>
		@endif
	</div>

	<x-ui.modal show="$wire.showModal" title="Penandatangan Setuju Bayar" :description="$instansi?->name"
		icon="fa-solid fa-signature text-primary-600" maxWidth="max-w-4xl">
		@if ($people)
			@php
				$blank = '_________________________';
				$pptkName = $pptk?->name ?? $blank;
				// Pratinjau = replika 1:1 tabel tanda tangan dokumen cetak (dompdf): lebar isi halaman F4
				// (21,5 cm − margin kiri-kanan), Times 9pt, p margin 1em & td padding 1px bawaan dompdf.
				// Sel yang diatur ditandai outline (tidak menggeser tata letak). Ditampilkan 80% (zoom, bukan
				// transform, agar ruang yang dipakai ikut mengecil) supaya modal tidak terlalu tinggi.
				$paper = 'overflow-x-auto rounded border border-slate-200 bg-slate-100 p-3';
				$sheet = 'mx-auto box-content bg-white px-4 py-3 text-black shadow-xs [zoom:0.8] [&_td_p]:my-[1em]';
				$sheetFont = "font-family: 'Times New Roman', Times, serif; font-size: 9pt; line-height: 1.4;";
				// Kotak penanda digambar sebagai lapisan ::before di belakang teks (tidak menggeser tata letak):
				// melebar ke kiri agar tidak rapat ke tulisan, dan menjorok dari tepi kanan agar tidak rapat ke
				// kolom sebelah. Baris selebar halaman (Pengeluaran Riil) melebar ke kedua sisi.
				$mark = 'relative isolate before:absolute before:inset-y-0 before:-z-10 before:rounded-sm before:border before:border-dashed before:border-amber-500 before:bg-amber-50/70';
				$markCol = $mark . ' before:-left-2 before:right-3';
				$markRow = $mark . ' before:-inset-x-2';
			@endphp
			{{-- Pratinjau dihitung di klien (Alpine) dari isian, tanpa request ke server: data pegawai
			     dari opsi yang dipilih (event option-chosen), label dari $wire.label. Isian baru dikirim
			     saat Simpan. Dibuat ulang tiap modal dibuka agar state pembukaan sebelumnya tidak tersisa. --}}
			<div class="space-y-5" wire:key="sb-form-{{ $opened }}"
				x-data="{
					people: @js($people),
					blank: @js($blank),
					get signer() { return this.people[String(this.$wire.signerId ?? '')] ?? null },
					get role() { return String(this.$wire.label ?? '').trim() || this.signer?.position || 'Kepala Dinas' },
					{{-- Kuitansi: label kosong dicetak PENGGUNA ANGGARAN (bukan nama jabatan), huruf kapital. --}}
					get kuitansiRole() { return (String(this.$wire.label ?? '').trim() || 'Pengguna Anggaran').toUpperCase() },
				}"
				@option-chosen="people[String($event.detail.value)] = $event.detail">
				<p class="text-sm leading-relaxed text-slate-600">
					Pejabat yang menandatangani kolom <strong class="text-slate-800">Setuju Bayar / Mengetahui</strong> pada
					kuitansi, pengeluaran riil, dan rincian biaya. Berlaku untuk semua SPPD {{ $instansi->name }}.
					Biarkan pegawai kosong untuk memakai Kepala OPD.
				</p>

				<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
					<x-form.searchable-select wire:model="signerId" label="Pegawai Penandatangan" searchMethod="searchSigners"
						:selectedLabel="$selectedLabel" placeholder="Kepala OPD (bawaan)" searchPlaceholder="Cari nama / NIP pegawai..." />
					<x-form.input wire:model="label" label="Label Jabatan"
						placeholder="Contoh: Kuasa Pengguna Anggaran"
						hint="Kosongkan untuk memakai nama jabatan penandatangan." />
				</div>

				{{-- Potongan bagian tanda tangan dokumen cetak, satu dokumen per tab; kotak bergaris =
				     yang diatur di sini. Semua panel ditumpuk di satu sel grid (yang tak aktif invisible)
				     agar tinggi modal tidak berubah saat pindah tab. --}}
				<div x-data="{ tab: 'riil' }">
					<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
						<p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pratinjau tanda tangan</p>
						<p class="flex items-center gap-1.5 text-[11px] text-slate-500">
							<span class="inline-block size-3 rounded-sm border border-dashed border-amber-500 bg-amber-50"></span>
							Bagian yang diatur di sini
						</p>
					</div>

					<div class="flex border-b border-slate-200" role="tablist">
						@foreach ([
							'riil' => ['fa-hand-holding-dollar', 'Pengeluaran Riil'],
							'rincian' => ['fa-calculator', 'Rincian Biaya'],
							'kuitansi' => ['fa-receipt', 'Kuitansi'],
						] as $key => [$tabIcon, $tabLabel])
							<button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
								:class="tab === '{{ $key }}' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
								class="-mb-px flex flex-1 cursor-pointer items-center justify-center gap-1.5 border-b-2 px-2 py-2.5 text-xs font-bold uppercase leading-tight tracking-normal transition-colors outline-none focus-visible:bg-primary-50 sm:tracking-wider">
								{{-- Ikon dibungkus span: display .fa-solid mengalahkan `hidden` bila dipasang langsung. --}}
								<span class="hidden sm:inline"><i class="fa-solid {{ $tabIcon }}"></i></span> {{ $tabLabel }}
							</button>
						@endforeach
					</div>

					<div class="grid grid-cols-1 pt-3">
						{{-- Pengeluaran Riil (margin halaman 2,3 cm → isi 16,9 cm) --}}
						<div role="tabpanel" class="min-w-0 [grid-area:1/1]" :class="tab === 'riil' ? '' : 'invisible'">
							<div class="{{ $paper }}">
								<div class="{{ $sheet }}" style="width: 16.9cm; {{ $sheetFont }}">
									<p class="text-right">Kendari, {{ $date }}</p>
									<table class="w-full border-collapse text-center">
										<tr>
											<td class="w-1/2 p-px align-top">
												<p>Menyetujui :<br>Pejabat Pelaksana Teknis Kegiatan</p>
												<div style="height: 50px;"></div>
												<p><span class="font-bold underline">{{ $pptkName }}</span><br>@if ($pptk?->nip)NIP. {{ $pptk->nip }}@endif</p>
											</td>
											<td class="w-1/2 p-px align-top">
												<p><br>Yang Melakukan Perjalanan Dinas</p>
												<div style="height: 50px;"></div>
												<p><span class="font-bold underline">{{ $sppd->user->name }}</span><br>@if ($sppd->user->nip)NIP. {{ $sppd->user->nip }}@endif</p>
											</td>
										</tr>
										<tr>
											<td colspan="2" class="px-px pb-px pt-[15px] align-top {{ $markRow }}">
												@include('livewire.sppd.partials.setuju-bayar-signature', ['heading' => 'Mengetahui :', 'gap' => 50])
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>

						{{-- Rincian Biaya: blok tanda tangan Perhitungan SPPD Rampung (margin 1,8 cm → isi 17,9 cm) --}}
						<div role="tabpanel" class="min-w-0 [grid-area:1/1]" :class="tab === 'rincian' ? '' : 'invisible'">
							<div class="{{ $paper }}">
								<div class="{{ $sheet }}" style="width: 17.9cm; {{ $sheetFont }}">
									<table class="w-full border-collapse text-left">
										<tr>
											<td class="w-[55%] p-px align-top leading-[1.7] {{ $markCol }}">
												@include('livewire.sppd.partials.setuju-bayar-signature', ['heading' => 'Setuju bayar:', 'gap' => 35])
											</td>
											<td class="w-[45%] p-px align-top leading-[1.7]">
												<p>Mengetahui/Menyetujui<br>Pejabat Pelaksana Teknis Kegiatan</p>
												<div style="height: 35px;"></div>
												<p><span class="font-bold underline">{{ $pptkName }}</span>@if ($pptk?->nip)<br>NIP. {{ $pptk->nip }}@endif</p>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>

						{{-- Kuitansi Panjar / Rampung (margin 1,5 cm → isi 18,5 cm) --}}
						<div role="tabpanel" class="min-w-0 [grid-area:1/1]" :class="tab === 'kuitansi' ? '' : 'invisible'">
							<div class="{{ $paper }}">
								<div class="{{ $sheet }}" style="width: 18.5cm; {{ $sheetFont }}">
									<p class="text-right uppercase">Kendari, {{ $date }}</p>
									<table class="w-full border-collapse text-left">
										<tr>
											<td class="w-[33%] p-px align-top {{ $markCol }}">
												@include('livewire.sppd.partials.setuju-bayar-signature', [
													'heading' => 'SETUJU BAYAR',
													'roleExpr' => 'kuitansiRole',
													'roleText' => Str::upper(trim($label) ?: 'Pengguna Anggaran'),
													'gap' => 55,
												])
											</td>
											<td class="w-[33%] p-px align-top">
												<p><br>BENDAHARA PENGELUARAN</p>
												<div style="height: 55px;"></div>
												<p><span class="font-bold underline">{{ $bendahara?->name ?? $blank }}</span><br>@if ($bendahara?->nip)NIP. {{ $bendahara->nip }}@endif</p>
											</td>
											<td class="w-[33%] p-px align-top">
												<p><br>YANG MENERIMA</p>
												<div style="height: 55px;"></div>
												<p><span class="font-bold underline">{{ $sppd->user->name }}</span><br>@if ($sppd->user->nip)NIP. {{ $sppd->user->nip }}@endif</p>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		@endif

		<x-slot name="footer" class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
			{{-- Reset hanya mengosongkan isian di klien (kembali ke Kepala OPD, pratinjau ikut berubah);
			     baru tersimpan saat Simpan, dan dibatalkan dengan Batal. --}}
			<div class="min-h-9">
				<x-ui.button variant="danger-ghost" x-show="String($wire.signerId ?? '') !== '' || String($wire.label ?? '').trim() !== ''"
					@click="$wire.signerId = ''; $wire.label = ''">
					<x-slot:icon><i class="fa-solid fa-rotate-left text-xs"></i></x-slot:icon>
					Reset pengaturan
				</x-ui.button>
			</div>
			<div class="flex items-center justify-end gap-2">
				{{-- Ditutup di klien (tanpa render ulang) agar isi tidak hilang saat animasi tutup. --}}
				<x-ui.button variant="secondary" @click="$wire.showModal = false">Batal</x-ui.button>
				<x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
					<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
					Simpan
				</x-ui.button>
			</div>
		</x-slot>
	</x-ui.modal>
</div>
