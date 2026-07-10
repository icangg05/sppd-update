<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
	<div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
		<div>
			<p class="text-[10px] font-bold uppercase text-slate-500">Pejabat Pelaksana Teknis Kegiatan (PPTK)</p>
			@if ($sppd->pptk_id && $sppd->pptk)
				<p class="mt-1 text-sm font-bold text-slate-800">{{ $sppd->pptk->name }}
					<span class="font-normal text-slate-500">{{ $sppd->pptk->nip ? '— NIP. ' . $sppd->pptk->nip : '' }}</span>
				</p>
			@else
				<p class="mt-1 text-sm font-bold text-rose-600">
					<i class="fa-solid fa-triangle-exclamation mr-1"></i> Belum diatur
				</p>
			@endif
		</div>

		@if ($this->canManage())
			<div class="flex w-full items-start gap-2 md:w-auto">
				{{-- Select dengan pencarian server-side --}}
				<div class="relative flex-1 md:flex-none" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
					<button type="button" @click="open = !open; if (open && window.matchMedia('(min-width: 768px)').matches) $nextTick(() => $refs.searchInput.focus())"
						class="flex w-full items-center justify-between gap-2 rounded border border-slate-300 px-3 py-1.5 text-left text-sm focus:border-emerald-500 focus:ring-emerald-500 md:w-64">
						<span class="truncate {{ $pptk_id ? 'text-slate-800' : 'text-slate-500' }}">
							{{ $selectedLabel ?? '— Pilih PPTK —' }}
						</span>
						<i class="fa-solid fa-chevron-down text-[10px] text-slate-500"></i>
					</button>

					<div x-show="open" x-cloak x-transition.origin.top
						class="absolute right-0 z-20 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg md:w-72">
						<div class="border-b border-slate-100 p-2">
							<div class="relative">
								<i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-slate-500"></i>
								<input type="text" x-ref="searchInput" wire:model.live.debounce.300ms="search" placeholder="Cari nama / NIP..."
									class="w-full rounded border border-slate-300 py-1.5 pl-7 pr-2.5 text-xs focus:border-emerald-500 focus:ring-emerald-500">
							</div>
						</div>
						<ul class="max-h-60 overflow-y-auto py-1 text-sm" wire:loading.class="opacity-50" wire:target="search">
							@forelse ($candidates as $candidate)
								<li>
									<button type="button"
										wire:click="selectPptk({{ $candidate->id }})" @click="open = false"
										class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left hover:bg-emerald-50 {{ $pptk_id == $candidate->id ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700' }}">
										<span class="truncate">{{ $candidate->name }}
											<span class="text-xs text-slate-500">{{ $candidate->nip ? '(' . $candidate->nip . ')' : '' }}</span>
										</span>
										@if ($pptk_id == $candidate->id)
											<i class="fa-solid fa-check text-xs text-emerald-600"></i>
										@endif
									</button>
								</li>
							@empty
								<li class="px-3 py-4 text-center text-xs text-slate-500">Tidak ada pegawai ditemukan.</li>
							@endforelse
						</ul>
					</div>
				</div>

				<button type="button" wire:click="updatePptk" wire:loading.attr="disabled" wire:target="updatePptk"
					class="shrink-0 rounded bg-slate-800 px-4 py-1.5 text-sm font-bold text-white transition hover:bg-slate-900 disabled:opacity-50">
					<span wire:loading.remove wire:target="updatePptk"><i class="fa-solid fa-floppy-disk mr-1"></i> Simpan</span>
					<span wire:loading wire:target="updatePptk"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
				</button>
			</div>
		@endif
	</div>

	@error('pptk_id')
		<p class="mt-2 text-xs text-rose-500"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p>
	@enderror
</div>
