<div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
	{{-- Aksen kiri: hijau bila PPTK sudah diatur, ambar bila belum. --}}
	<div class="flex flex-col gap-4 border-l-2 {{ $sppd->pptk_id ? 'border-emerald-500' : 'border-amber-400' }} p-5 md:flex-row md:items-end md:justify-between">
		<div class="min-w-0">
			<p class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
				<i class="fa-solid fa-user-tie text-emerald-600"></i>
				Pejabat Pelaksana Teknis Kegiatan (PPTK)
			</p>

			@if ($sppd->pptk_id && $sppd->pptk)
				<p class="mt-1.5 truncate text-base font-bold text-slate-900">{{ $sppd->pptk->name }}</p>
				<p class="text-xs font-medium text-slate-500">
					{{ $sppd->pptk->nip ? 'NIP. ' . $sppd->pptk->nip : 'NIP tidak tersedia' }}
				</p>
			@else
				<p class="mt-1.5 flex items-center gap-1.5 text-sm font-bold text-amber-600">
					<i class="fa-solid fa-triangle-exclamation"></i> Belum diatur
				</p>
				<p class="text-xs font-medium text-slate-500">Pilih PPTK terlebih dahulu untuk membuka tombol cetak.</p>
			@endif
		</div>

		@if ($this->canManage())
			<div class="flex w-full flex-col gap-2 sm:flex-row sm:items-stretch md:w-auto">
				<x-form.searchable-select
					wire:model="pptk_id"
					:options="$options"
					placeholder="— Pilih PPTK —"
					searchPlaceholder="Cari nama / NIP..."
					wrapperClass="w-full sm:w-72" />

				<x-ui.button
					variant="primary"
					size="sm"
					wire:click="updatePptk"
					wire:target="updatePptk"
					class="shrink-0">
					<x-slot name="icon"><i class="fa-solid fa-floppy-disk text-xs"></i></x-slot>
					Simpan
				</x-ui.button>
			</div>
		@endif
	</div>
</div>
