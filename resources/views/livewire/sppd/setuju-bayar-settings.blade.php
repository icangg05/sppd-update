<div>
	{{-- Ringkasan penandatangan Setuju Bayar yang berlaku untuk dokumen SPPD ini. --}}
	<div class="dash-enter flex h-full flex-col gap-3 rounded border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center">
		<div class="flex min-w-0 flex-1 items-center gap-3">
			<span class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
				<i class="fa-solid fa-signature"></i>
			</span>
			<div class="min-w-0 leading-tight">
				<p class="text-sm font-bold text-slate-800">Penandatangan Setuju Bayar</p>
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

				{{-- Potongan bagian tanda tangan dokumen cetak; kotak bergaris = yang diatur di sini. --}}
				<div>
					<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
						<p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pratinjau tanda tangan</p>
						<p class="flex items-center gap-1.5 text-[11px] text-slate-500">
							<span class="inline-block size-3 rounded-sm border border-dashed border-amber-500 bg-amber-50"></span>
							Bagian yang diatur di sini
						</p>
					</div>

					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						{{-- Pengeluaran Riil --}}
						<figure class="min-w-0">
							<figcaption class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
								<i class="fa-solid fa-hand-holding-dollar text-primary-500"></i> Laporan Pengeluaran Riil
							</figcaption>
							<div class="rounded border border-slate-200 bg-white px-4 py-4 font-serif text-[11px] leading-snug text-slate-900 shadow-xs">
								<p class="text-right">Kendari, {{ $date }}</p>
								<div class="mt-2 grid grid-cols-2 gap-2 text-center">
									<div>
										<p>Menyetujui :<br>Pejabat Pelaksana Teknis Kegiatan</p>
										<div class="h-7"></div>
										<p><span class="font-bold underline">{{ $pptkName }}</span>@if ($pptk?->nip)<br>NIP. {{ $pptk->nip }}@endif</p>
									</div>
									<div>
										<p><br>Yang Melakukan Perjalanan Dinas</p>
										<div class="h-7"></div>
										<p><span class="font-bold underline">{{ $sppd->user->name }}</span>@if ($sppd->user->nip)<br>NIP. {{ $sppd->user->nip }}@endif</p>
									</div>
								</div>
								<div class="mx-auto mt-3 w-fit max-w-full rounded border border-dashed border-amber-500 bg-amber-50/70 px-4 py-1.5 text-center">
									@include('livewire.sppd.partials.setuju-bayar-signature', ['heading' => 'Mengetahui :'])
								</div>
							</div>
						</figure>

						{{-- Rincian Biaya: blok tanda tangan Perhitungan SPPD Rampung --}}
						<figure class="min-w-0">
							<figcaption class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
								<i class="fa-solid fa-calculator text-primary-500"></i> Rincian Biaya Perjalanan Dinas
							</figcaption>
							<div class="rounded border border-slate-200 bg-white px-4 py-4 font-serif text-[11px] leading-snug text-slate-900 shadow-xs">
								{{-- Dokumen ini rata kiri (berbeda dengan Pengeluaran Riil yang rata tengah). --}}
								<div class="grid grid-cols-[55%_45%] gap-2 text-left leading-[1.7]">
									<div class="w-fit max-w-full rounded border border-dashed border-amber-500 bg-amber-50/70 px-2 py-1.5">
										@include('livewire.sppd.partials.setuju-bayar-signature', ['heading' => 'Setuju bayar:'])
									</div>
									<div class="px-2 py-1.5">
										<p>Mengetahui/Menyetujui<br>Pejabat Pelaksana Teknis Kegiatan</p>
										<div class="h-7"></div>
										<p><span class="font-bold underline">{{ $pptkName }}</span>@if ($pptk?->nip)<br>NIP. {{ $pptk->nip }}@endif</p>
									</div>
								</div>
							</div>
						</figure>
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
