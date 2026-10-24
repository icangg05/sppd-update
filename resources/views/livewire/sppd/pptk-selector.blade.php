<div>
	{{-- Ringkasan PPTK SPPD ini; diubah lewat modal. --}}
	<div class="dash-enter flex h-full flex-col gap-3 rounded border bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center {{ $sppd->pptk ? 'border-slate-200' : 'border-amber-300' }}">
		<div class="flex min-w-0 flex-1 items-center gap-3">
			<span class="flex size-9 shrink-0 items-center justify-center rounded ring-1 {{ $sppd->pptk ? 'bg-primary-50 text-primary-600 ring-primary-100' : 'bg-amber-50 text-amber-600 ring-amber-200' }}">
				<i class="fa-solid fa-user-tie"></i>
			</span>
			<div class="min-w-0 leading-tight">
				{{-- Cakupan: PPTK diatur per SPPD (beda dengan Setuju Bayar yang berlaku per instansi). --}}
				<div class="flex flex-wrap items-center gap-x-2 gap-y-1">
					<p class="text-sm font-bold text-slate-800">PPTK</p>
					<x-ui.badge color="cyan"><i class="fa-solid fa-route mr-1 text-[9px]"></i> Khusus perjalanan ini</x-ui.badge>
				</div>
				<p class="mt-0.5 truncate text-xs text-slate-500">
					@if ($sppd->pptk)
						<span class="font-semibold text-slate-700">{{ $sppd->pptk->name }}</span>
						@if ($sppd->pptk->nip)
							· NIP. {{ $sppd->pptk->nip }}
						@endif
					@else
						<span class="text-amber-700">Belum diatur. Wajib sebelum mencetak pengeluaran riil &amp; rincian biaya.</span>
					@endif
				</p>
			</div>
		</div>
		@if ($this->canManage())
			<x-ui.button variant="secondary" size="sm" wire:click="open" class="shrink-0 self-start sm:self-auto">
				<x-slot:icon><i class="fa-solid fa-pen-to-square text-xs"></i></x-slot:icon>
				Atur
			</x-ui.button>
		@endif
	</div>

	<x-ui.modal show="$wire.showModal" title="Pejabat Pelaksana Teknis Kegiatan (PPTK)" description="Khusus SPPD ini"
		icon="fa-solid fa-user-tie text-primary-600" maxWidth="max-w-lg">
		{{-- Opsi pegawai dicari di server saat dropdown dibuka (searchMethod). Dibuat ulang
		     tiap modal dibuka agar label pilihan yang batal disimpan tidak tersisa. --}}
		<div class="space-y-4" wire:key="pptk-form-{{ $opened }}">
			<p class="text-sm leading-relaxed text-slate-600">
				PPTK menandatangani kolom <strong class="text-slate-800">Menyetujui</strong> pada Laporan Pengeluaran Riil dan
				<strong class="text-slate-800">Mengetahui/Menyetujui</strong> pada Rincian Biaya. Wajib diatur sebelum kedua dokumen itu bisa dicetak.
			</p>
			<x-form.searchable-select wire:model="pptk_id" label="Pegawai PPTK" searchMethod="searchPptk" :selectedLabel="$selectedLabel"
				placeholder="— Pilih PPTK —" searchPlaceholder="Cari nama / NIP..." />
		</div>

		<x-slot name="footer" class="flex items-center justify-end gap-2">
			{{-- Ditutup di klien (tanpa render ulang) agar isi tidak hilang saat animasi tutup. --}}
			<x-ui.button variant="secondary" @click="$wire.showModal = false">Batal</x-ui.button>
			<x-ui.button variant="primary" wire:click="updatePptk" wire:loading.attr="disabled" wire:target="updatePptk">
				<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
				Simpan
			</x-ui.button>
		</x-slot>
	</x-ui.modal>
</div>
