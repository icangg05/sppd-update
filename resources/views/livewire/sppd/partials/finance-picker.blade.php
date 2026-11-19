{{-- Modal pilih PPTK / KPA / Bendahara SPPD ini (App\Livewire\Sppd\FinanceSigners): tab Pilih Pegawai dan
     tab pratinjau tiap dokumen yang memuat peran itu. $financeModal dari FinanceSigners::financeModalData(). --}}
<x-ui.modal show="$wire.showFinanceModal" :title="$financeModal ? 'Pilih ' . $financeModal['meta']['full'] : 'Pilih pejabat keuangan'"
	:description="$financeModal ? 'Pelaksana: ' . $financeModal['pelaksana'] : null"
	icon="fa-solid fa-file-signature text-primary-600" maxWidth="max-w-3xl">
	@if ($financeModal)
		{{-- Kunci per pembukaan: tiap modal dibuka, tab kembali ke Pilih Pegawai dan daftar mulai dari atas. --}}
		<div wire:key="finance-modal-{{ $financeModal['role'] }}-{{ $financeOpenCount }}" x-data="{ tab: 'pilih' }" class="space-y-4">
			<div class="-mt-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
				<div role="tablist" class="flex min-w-max border-b border-slate-200">
					@foreach (array_merge([['key' => 'pilih', 'tab' => 'Pilih Pegawai']], $financeModal['previews']) as $financeTab)
						<button type="button" role="tab" @click="tab = '{{ $financeTab['key'] }}'"
							:aria-selected="tab === '{{ $financeTab['key'] }}'"
							:class="tab === '{{ $financeTab['key'] }}' ? 'border-primary-500 text-primary-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
							class="-mb-px flex cursor-pointer items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2 text-xs font-bold transition-colors">
							<i class="{{ $financeTab['key'] === 'pilih' ? 'fa-solid fa-user-check' : 'fa-regular fa-file-lines' }} text-[11px]"></i>
							{{ $financeTab['tab'] }}
						</button>
					@endforeach
				</div>
			</div>

			<div x-show="tab === 'pilih'" class="space-y-4">
				<p class="text-sm leading-relaxed text-slate-600">
					@if ($financeModal['meta']['desc'])
						{{ $financeModal['meta']['desc'] }}
					@endif
					Berlaku untuk SPPD ini dan disimpan sebagai pilihan perjalanan berikutnya pegawai pelaksana.
				</p>

				<x-form.input wire:model.live.debounce.300ms="financeSearch" icon="fa-solid fa-magnifying-glass"
					loadingTarget="financeSearch" placeholder="Cari nama / NIP pegawai..." data-autofocus />

				<ul class="max-h-[40vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
					@forelse ($financeModal['candidates'] as $row)
						@php $selected = $row['id'] === $financeChoiceId; @endphp
						{{-- Nama & keterangan selalu tampil penuh (dibungkus ke baris berikutnya, tidak dipotong). --}}
						<li wire:key="finance-candidate-{{ $row['id'] }}" class="flex items-center gap-3 px-3 py-2 {{ $selected ? 'bg-primary-50' : '' }}">
							<div class="min-w-0 flex-1 leading-tight">
								<p class="text-sm font-semibold text-slate-800 wrap-break-word">{{ $row['name'] }}</p>
								<p class="mt-0.5 text-[11px] text-slate-500 wrap-break-word">
									{{ collect([$row['nip'] ? 'NIP. ' . $row['nip'] : null, $row['position'], $row['unit']])->filter()->implode(' · ') }}
								</p>
							</div>
							<x-ui.button :variant="$selected ? 'primary' : 'secondary'" class="shrink-0 px-2.5 py-1 text-xs"
								wire:click="pickFinance({{ $row['id'] }})">
								@if ($selected)<i class="fa-solid fa-check text-[10px]"></i> Dipilih @else Pilih @endif
							</x-ui.button>
						</li>
					@empty
						<li class="px-3 py-3 text-xs italic text-slate-500">
							@if (trim($financeSearch) !== '')
								Pegawai tidak ditemukan. Coba kata kunci lain.
							@elseif ($financeModal['role'] === 'bendahara')
								Belum ada pegawai aktif berjabatan Bendahara di instansi pelaksana.
							@else
								Belum ada pegawai aktif di instansi pelaksana.
							@endif
						</li>
					@endforelse
				</ul>
				@error('financeChoiceId')
					<p class="text-xs font-semibold text-red-600">{{ $message }}</p>
				@enderror

				@if ($financeModal['role'] === 'bendahara')
					<p class="flex items-start gap-1.5 text-xs leading-relaxed text-slate-500">
						<i class="fa-solid fa-circle-info mt-0.5 text-slate-400"></i>
						<span>
							Nama belum tampil? Atur jabatan pegawainya menjadi Bendahara di
							@if ($financeModal['canManageEmployees'])
								<a href="{{ route('master.users.index') }}" target="_blank" rel="noopener"
									class="font-semibold text-primary-700 underline underline-offset-2 hover:text-primary-800">halaman Data Pegawai<i
										class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px]"></i></a>.
							@else
								halaman Data Pegawai (oleh Admin OPD).
							@endif
						</span>
					</p>
				@endif

				@if ($financeModal['role'] === 'kpa')
					<x-form.input wire:model="financeLabel" label="Label Jabatan Tercetak" placeholder="{{ \App\Services\SppdFinanceOfficials::DEFAULT_KPA_LABEL }}"
						hint="Bawaan: Kuasa Pengguna Anggaran. Ubah bila perlu, mis. Pengguna Anggaran." />
				@endif
			</div>

			@foreach ($financeModal['previews'] as $financeDoc)
				<div x-show="tab === '{{ $financeDoc['key'] }}'" x-cloak wire:key="finance-preview-{{ $financeDoc['key'] }}">
					@include('livewire.sppd.partials.finance-preview', ['doc' => $financeDoc])
				</div>
			@endforeach
		</div>
	@endif

	<x-slot name="footer" class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
		<div class="min-h-9">
			@if ($financeModal['hasCurrent'] ?? false)
				<x-ui.button variant="danger-ghost" wire:click="clearFinance" wire:target="clearFinance">
					<x-slot:icon><i class="fa-solid fa-eraser text-xs"></i></x-slot:icon>
					Kosongkan
				</x-ui.button>
			@endif
		</div>
		<div class="flex items-center justify-end gap-2">
			<x-ui.button variant="secondary" @click="$wire.showFinanceModal = false">Batal</x-ui.button>
			<x-ui.button variant="primary" wire:click="saveFinance" wire:target="saveFinance" :disabled="! $financeChoiceId">
				<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
				Simpan
			</x-ui.button>
		</div>
	</x-slot>
</x-ui.modal>
