{{-- Wizard Lengkapi / Ganti Pejabat (dipakai Buat SPPD & menu Walikota & Wakil Walikota):
     1 pilih pegawai → 2 pilih/tambah jabatan (bila jabatannya belum sesuai) → 3 konfirmasi.
     Jabatan & role tersimpan permanen, jadi berlaku juga untuk pengajuan berikutnya.

     Komponen pemakai menyediakan properti showOfficialModal, officialSearch, officialPositionId,
     method pickOfficialCandidate / chooseOfficialPosition / officialNext / officialBack /
     confirmOfficial, serta $official (lihat SppdCreate::render). Opsional:
     - $pelaksanaId      : tandai calon yang juga pelaksana SPPD
     - $pelaksanaOrigin  : info instansi pelaksana (Super Admin)
     - $allowNewPosition : tombol "Tambah jabatan baru" (butuh <livewire:position-form-modal />) --}}
@php $outgoing = $official['outgoing'] ?? null; @endphp
<x-ui.modal show="$wire.showOfficialModal" :title="($outgoing ? 'Ganti ' : 'Lengkapi ') . ($official['label'] ?? 'Pejabat')"
	:description="$official ? 'Langkah ' . $official['step'] . ' dari 3' : null"
	:icon="$outgoing ? 'fa-solid fa-user-pen text-amber-600' : 'fa-solid fa-user-plus text-amber-600'" maxWidth="max-w-2xl">
	@if ($official)
		@php
			$wizardSteps = [1 => $outgoing ? 'Pilih pengganti' : 'Pilih pegawai', 2 => 'Jabatan', 3 => 'Konfirmasi'];
			$currentStep = $official['step'];
			$candidate = $official['candidate'];
		@endphp
		<div class="space-y-4">
			{{-- Penanda langkah --}}
			<ol class="flex items-center gap-2 text-[11px] font-semibold">
				@foreach ($wizardSteps as $n => $stepLabel)
					@php
						$done = $currentStep > $n;
						$active = $currentStep === $n;
					@endphp
					<li class="flex items-center gap-1.5 {{ $active ? 'text-primary-700' : ($done ? 'text-emerald-700' : 'text-slate-400') }}">
						<span class="flex size-5 items-center justify-center rounded-full text-[10px] font-bold {{ $active ? 'bg-primary-600 text-white' : ($done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
							@if ($done)<i class="fa-solid fa-check text-[9px]"></i>@else{{ $n }}@endif
						</span>
						{{ $stepLabel }}
					</li>
					@if (! $loop->last)
						<li class="h-px w-6 bg-slate-200" aria-hidden="true"></li>
					@endif
				@endforeach
			</ol>

			@if ($pelaksanaOrigin ?? null)
				<p class="text-xs text-slate-500">
					<i class="fa-solid fa-building mr-1 text-[10px] text-slate-400"></i>Instansi: <strong class="font-semibold text-slate-700">{{ $pelaksanaOrigin['instansi'] }}</strong>
					@if ($pelaksanaOrigin['unit']) › {{ $pelaksanaOrigin['unit'] }} @endif
				</p>
			@endif

			@if ($currentStep === 1)
				{{-- Langkah 1: pilih pegawai --}}
				<p class="text-sm text-slate-600">
					@if ($outgoing)
						Siapa yang menggantikan <strong class="font-semibold text-slate-800">{{ $outgoing['name'] }}</strong>
						sebagai <strong class="font-semibold text-slate-800">{{ $official['label'] }}</strong>?
					@else
						Siapa yang menjabat <strong class="font-semibold text-slate-800">{{ $official['label'] }}</strong>?
					@endif
					@if ($official['cityWide'])
						<span class="block text-xs text-slate-500">Calon dari pegawai Sekretariat Daerah.</span>
					@endif
				</p>

				<x-form.input wire:model.live.debounce.300ms="officialSearch" icon="fa-solid fa-magnifying-glass"
					loadingTarget="officialSearch" placeholder="Cari nama / NIP pegawai..." data-autofocus />

				<ul class="max-h-[45vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
					@forelse ($official['candidates'] as $row)
						<li wire:key="official-candidate-{{ $row['id'] }}" class="flex items-center gap-3 px-3 py-2">
							<div class="min-w-0 flex-1 leading-tight">
								<p class="truncate text-sm font-semibold text-slate-800">
									{{ $row['name'] }}
									@if ($row['id'] === ($pelaksanaId ?? null))
										<x-ui.badge bg="bg-primary-50" text="text-primary-700" class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold">Pelaksana</x-ui.badge>
									@endif
								</p>
								<p class="truncate text-[11px] text-slate-500">
									{{ $row['nip'] ?: 'Tanpa NIP' }} · {{ $row['position'] ?? 'Jabatan belum diisi' }}
								</p>
							</div>
							@if ($row['fits'])
								<x-ui.badge bg="bg-emerald-50" text="text-emerald-700" class="shrink-0 px-1.5 py-0.5 text-[10px]">Jabatan sesuai</x-ui.badge>
							@endif
							<x-ui.button variant="secondary" class="shrink-0 px-2.5 py-1 text-xs"
								wire:click="pickOfficialCandidate({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="pickOfficialCandidate">
								Pilih
							</x-ui.button>
						</li>
					@empty
						<li class="px-3 py-3 text-xs italic text-slate-500">
							{{ trim($officialSearch) !== '' ? 'Pegawai tidak ditemukan. Coba kata kunci lain.' : 'Belum ada pegawai Staf di instansi ini.' }}
						</li>
					@endforelse
				</ul>

				<p class="text-xs text-slate-500">
					Pegawainya belum terdaftar?
					<a href="{{ route('master.users.create') }}" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline-offset-2 hover:underline">Tambah pegawai</a>
					di tab baru, lalu cari lagi di sini.
				</p>
			@elseif ($currentStep === 2)
				{{-- Langkah 2: jabatan pegawai belum sesuai → pilih atau tambah jabatan --}}
				<p class="text-sm text-slate-600">
					Jabatan <strong class="font-semibold text-slate-800">{{ $candidate['name'] }}</strong>
					({{ $candidate['position'] ?? 'belum diisi' }}) belum sesuai. Pilih jabatan {{ $official['label'] }}:
				</p>

				<ul class="max-h-[35vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
					@forelse ($official['positions'] as $pos)
						@php $selected = $pos['id'] === $officialPositionId; @endphp
						<li wire:key="official-position-{{ $pos['id'] }}"
							class="flex items-center gap-3 px-3 py-2 {{ $selected ? 'bg-primary-50' : '' }}">
							<div class="min-w-0 flex-1 leading-tight">
								<p class="truncate text-sm font-semibold {{ $pos['holder'] ? 'text-slate-400' : 'text-slate-800' }}">
									{{ $pos['name'] }}
									@if ($pos['suggested'])
										<x-ui.badge bg="bg-amber-50" text="text-amber-700" class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold">Disarankan</x-ui.badge>
									@endif
								</p>
								@if ($pos['holder'])
									<p class="truncate text-[11px] text-slate-400">Sudah dijabat {{ $pos['holder'] }}</p>
								@elseif ($pos['handover'])
									<p class="truncate text-[11px] text-slate-500">Jabatan {{ $pos['handover'] }} saat ini, dialihkan ke pengganti</p>
								@endif
							</div>
							<x-ui.button :variant="$selected ? 'primary' : 'secondary'" class="shrink-0 px-2.5 py-1 text-xs"
								wire:click="chooseOfficialPosition({{ $pos['id'] }})" :disabled="(bool) $pos['holder']">
								@if ($selected)<i class="fa-solid fa-check text-[10px]"></i> Dipilih @else Pilih @endif
							</x-ui.button>
						</li>
					@empty
						<li class="px-3 py-3 text-xs italic text-slate-500">Belum ada jabatan {{ $official['label'] }}. {{ ($allowNewPosition ?? true) ? 'Tambahkan di bawah.' : 'Tambahkan lewat Data Jabatan.' }}</li>
					@endforelse
				</ul>

				{{-- Jabatan belum ada di daftar: isi lengkap lewat form Tambah Jabatan (sama dengan
				     halaman Data Jabatan); tingkatnya dikunci ke tahap ini, lalu langsung terpilih. --}}
				@if ($allowNewPosition ?? true)
					<div class="flex flex-wrap items-center justify-between gap-2">
						<p class="text-xs text-slate-500">Jabatannya belum ada di daftar?</p>
						<x-ui.button variant="secondary" x-on:click="$dispatch('open-position-form', { role: '{{ $official['role'] }}' })">
							<x-slot:icon><i class="fa-solid fa-plus text-xs"></i></x-slot:icon>
							Tambah jabatan baru
						</x-ui.button>
					</div>
				@endif
			@else
				{{-- Langkah 3: konfirmasi --}}
				<dl class="divide-y divide-slate-100 rounded border border-slate-200 text-sm">
					<div class="flex gap-3 px-3 py-2">
						<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $outgoing ? 'Pengganti' : 'Pegawai' }}</dt>
						<dd class="min-w-0 font-semibold text-slate-800">{{ $candidate['name'] }} <span class="font-normal text-slate-500">· {{ $candidate['nip'] ?: 'Tanpa NIP' }}</span></dd>
					</div>
					<div class="flex gap-3 px-3 py-2">
						<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Jabatan</dt>
						<dd class="min-w-0 text-slate-800">
							@if ($official['position'])
								<span class="text-slate-400 line-through">{{ $candidate['position'] ?? 'Belum diisi' }}</span>
								<i class="fa-solid fa-arrow-right mx-1 text-[10px] text-slate-400"></i>
								<strong class="font-semibold">{{ $official['position']['name'] }}</strong>
							@else
								{{ $candidate['position'] }} <span class="text-slate-500">(tetap)</span>
							@endif
						</dd>
					</div>
					<div class="flex gap-3 px-3 py-2">
						<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</dt>
						<dd class="min-w-0 font-semibold text-slate-800">{{ $official['label'] }}</dd>
					</div>
					@if ($outgoing)
						<div class="flex gap-3 px-3 py-2">
							<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Pejabat lama</dt>
							<dd class="min-w-0 text-slate-800">
								<strong class="font-semibold">{{ $outgoing['name'] }}</strong> menjadi {{ $outgoing['newRole'] }}.
								@if ($outgoing['released'])
									<span class="block text-xs text-slate-500">Jabatan {{ $outgoing['released'] }} dilepas. Atur jabatan barunya lewat Data Pegawai.</span>
								@endif
							</dd>
						</div>
					@endif
				</dl>
				@if ($outgoing && $outgoing['pending'] > 0)
					<x-ui.alert variant="warning" icon="fa-hourglass-half">
						{{ $outgoing['name'] }} masih memiliki <strong class="font-semibold">{{ $outgoing['pending'] }} persetujuan SPPD</strong> yang menunggu. Persetujuan itu tetap pada {{ $outgoing['name'] }}.
					</x-ui.alert>
				@endif
				<p class="text-xs text-slate-500">{{ $official['cityWide'] ? 'Berlaku untuk seluruh pengajuan SPPD.' : 'Berlaku juga untuk pengajuan berikutnya dari instansi ini.' }}</p>
			@endif
		</div>
	@endif

	<x-slot name="footer" class="flex items-center justify-between gap-2">
		@if (($official['step'] ?? 1) > 1)
			<x-ui.button variant="secondary" wire:click="officialBack">
				<x-slot:icon><i class="fa-solid fa-arrow-left text-xs"></i></x-slot:icon>
				Kembali
			</x-ui.button>
		@else
			<x-ui.button variant="secondary" wire:click="$set('showOfficialModal', false)">Tutup</x-ui.button>
		@endif

		@if (($official['step'] ?? 1) === 2)
			<x-ui.button variant="primary" wire:click="officialNext" :disabled="! $officialPositionId">
				Lanjut <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
			</x-ui.button>
		@elseif (($official['step'] ?? 1) === 3)
			<x-ui.button variant="primary" wire:click="confirmOfficial" wire:loading.attr="disabled" wire:target="confirmOfficial">
				<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
				{{ $outgoing ? 'Ganti Pejabat' : 'Tetapkan' }}
			</x-ui.button>
		@endif
	</x-slot>
</x-ui.modal>
