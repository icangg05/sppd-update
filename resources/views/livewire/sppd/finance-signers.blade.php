<div>
	{{-- PPTK, KPA, Bendahara SPPD ini: dicatat dari pilihan tersimpan pegawai pelaksana saat diajukan, atau
	     dipilih di sini (sekaligus disimpan untuk perjalanan berikutnya pegawai itu). --}}
	<div class="dash-enter rounded border bg-white px-4 py-3 shadow-sm {{ $missing ? 'border-amber-300' : 'border-slate-200' }}">
		<div class="mb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
			<div class="flex items-center gap-2">
				<p class="text-sm font-bold text-slate-800">Pejabat Keuangan</p>
				@if ($missing)
					<x-ui.badge color="amber">{{ $missing }} belum dipilih</x-ui.badge>
				@endif
			</div>
			<p class="text-[11px] text-slate-500">
				Dipakai kuitansi, pengeluaran riil, dan rincian biaya.
				@if ($canManage)
					Perubahan juga disimpan untuk perjalanan berikutnya {{ $sppd->user->name }}.
				@endif
			</p>
		</div>
		<div class="grid grid-cols-1 gap-2.5 md:grid-cols-3">
			@foreach (\App\Services\SppdFinanceOfficials::ROLES as $financeRoleKey => $financeMeta)
				@php
					$item = $items[$financeRoleKey] ?? ['user' => null, 'label' => null, 'warning' => null];
					$official = $item['user'];
					$caption = match (true) {
						! $official => null,
						$financeRoleKey === 'pptk' => $official->nip ? 'NIP. ' . $official->nip : ($official->position?->name ?? null),
						default => $item['label'],
					};
				@endphp
				<div wire:key="finance-tile-{{ $financeRoleKey }}" @class([
					'flex min-w-0 items-start gap-3 rounded border px-3 py-2.5',
					'border-slate-200 bg-white' => $official && ! $item['warning'],
					'border-amber-300 bg-amber-50/40' => $official && $item['warning'],
					'border-dashed border-slate-300 bg-slate-50/60' => ! $official,
				])>
					<span @class([
						'flex size-8 shrink-0 items-center justify-center rounded ring-1',
						'bg-primary-50 text-primary-600 ring-primary-100' => $official,
						'bg-white text-slate-400 ring-slate-200' => ! $official,
					])>
						<i class="fa-solid {{ $financeMeta['icon'] }} text-sm"></i>
					</span>
					<div class="min-w-0 flex-1 leading-tight">
						<p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $financeMeta['title'] }}</p>
						@if ($official)
							<p class="mt-0.5 text-sm font-semibold text-slate-800 wrap-break-word">{{ $official->name }}</p>
							@if ($caption)
								<p class="mt-0.5 text-[11px] text-slate-500 wrap-break-word">{{ $caption }}</p>
							@endif
							@if ($item['warning'])
								<p class="mt-1 text-[11px] font-semibold leading-snug text-amber-700">
									<i class="fa-solid fa-triangle-exclamation mr-0.5"></i>{{ $item['warning'] }}
								</p>
							@endif
						@else
							<p class="mt-0.5 text-sm italic text-slate-400">Belum dipilih</p>
						@endif
					</div>
					@if ($canManage)
						<x-ui.button variant="secondary" size="xs" wire:click="openFinance('{{ $financeRoleKey }}')" class="shrink-0"
							title="{{ ($official ? 'Ubah ' : 'Pilih ') . $financeMeta['full'] }}">
							<x-slot:icon><i class="fa-solid {{ $official ? 'fa-pen-to-square' : 'fa-hand-pointer' }} text-[9px]"></i></x-slot:icon>
							{{ $official ? 'Ubah' : 'Pilih' }}
						</x-ui.button>
					@endif
				</div>
			@endforeach
		</div>
	</div>

	@include('livewire.sppd.partials.finance-picker')
</div>
