<div class="p-1 space-y-4">
	@php $isDprd = $this->isDprd(); @endphp

	{{-- Header (title card) --}}
	<div
		class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
		{{-- Watermark institusional (tipis, hanya karakter). --}}
		<i class="fa-solid {{ $isDprd ? 'fa-landmark-dome' : 'fa-users-gear' }} pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
			aria-hidden="true"></i>

		<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="min-w-0 leading-tight">
				<span
					class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
					<i class="fa-solid fa-user-shield text-[9px]"></i>
					{{ $isDprd ? 'Keanggotaan DPRD' : 'Manajemen Kepegawaian' }}
					<span class="ml-1 tabular-nums text-primary-600/70">· {{ $users->total() }}</span>
				</span>
				<h1 class="text-xl font-bold tracking-tight text-slate-800">
					{{ $isDprd ? 'Data Anggota DPRD' : 'Data Pegawai' }}
				</h1>
				<p class="mt-1 text-xs text-slate-500">Kelola data pegawai dan hak akses pengguna sistem</p>
			</div>
			<x-ui.button href="{{ route('master.users.create', array_filter(['type' => $type])) }}" variant="primary"
				class="shrink-0 font-bold">
				<i class="fa-solid fa-plus text-xs"></i> {{ $isDprd ? 'Tambah Anggota DPRD' : 'Tambah Pegawai' }}
			</x-ui.button>
		</div>
	</div>

	{{-- Filters --}}
	{{-- TANPA .dash-enter: animasi (fill: both) menjebak dropdown fixed searchable-select. --}}
	<div class="rounded border border-slate-200 bg-white p-4 shadow-sm">
		<div class="flex flex-col sm:flex-row gap-3">

			{{-- Search Input with Icon --}}
			<x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
				loadingTarget="search" wrapperClass="flex-1" placeholder="{{ $this->searchPlaceholder() }}" />

			{{-- DPRD: filter Partai/Fraksi menggantikan filter Instansi --}}
			@if ($isDprd)
				@php
					$partaiFilterOptions = collect($partaiList)
						->map(fn($p) => ['value' => $p, 'label' => $p])
						->prepend(['value' => '', 'label' => 'Semua Partai'])
						->all();
				@endphp
				<div class="w-full sm:w-64">
					<x-form.searchable-select wire:model.live="partai" name="partai"
						:options="$partaiFilterOptions" placeholder="Semua Partai"
						searchPlaceholder="Cari partai / fraksi..." class="bg-slate-50" />
				</div>
			{{-- Department Dropdown (Super Admin) --}}
			@elseif (auth()->user()->hasRole('super_admin'))
				@php
					$departmentFilterOptions = collect($departments)
						->map(fn($d) => ['value' => $d->id, 'label' => $d->display_name])
						->prepend(['value' => '', 'label' => 'Semua Instansi'])
						->all();
				@endphp
				<div class="w-full sm:w-64">
					<x-form.searchable-select wire:model.live="department_id" name="department_id"
						:options="$departmentFilterOptions" placeholder="Semua Instansi"
						searchPlaceholder="Cari instansi..." class="bg-slate-50" />
				</div>
			@endif

			{{-- Filter Role (searchable) — hanya role yang tersedia pada konteks halaman ini --}}
			@php
				$roleFilterOptions = collect($availableRoles)
					->map(fn($r) => ['value' => $r->name, 'label' => $r->label ?? $r->name])
					->prepend(['value' => '', 'label' => 'Semua Role'])
					->all();
			@endphp
			<div class="w-full sm:w-56">
				<x-form.searchable-select wire:model.live="role" name="role"
					:options="$roleFilterOptions" placeholder="Semua Role"
					searchPlaceholder="Cari role..." class="bg-slate-50" />
			</div>

			{{-- Reset --}}
			@php $hasActiveFilters = $search !== '' || $department_id !== '' || $partai !== '' || $position_id !== '' || $rank_id !== '' || $role !== ''; @endphp
			<div class="flex items-center gap-2">
				<x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="!$hasActiveFilters">
					<x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
					Reset
				</x-ui.button>
			</div>
		</div>

		{{-- Indikator filter dari halaman master (Data Jabatan / Data Pangkat / Kelola Role) --}}
		@if ($activePosition || $activeRank || $activeRole)
			<div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
				@if ($activePosition)
					<span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 font-semibold text-primary-700 ring-1 ring-primary-600/20">
						<i class="fa-solid fa-id-badge text-[10px]"></i>
						Jabatan: {{ $activePosition->name }}
						<button type="button" wire:click="$set('position_id', '')" class="ml-1 text-primary-500 hover:text-primary-800" title="Hapus filter">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				@endif
				@if ($activeRank)
					<span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 font-semibold text-indigo-700 ring-1 ring-indigo-600/20">
						<i class="fa-solid fa-ranking-star text-[10px]"></i>
						Pangkat: {{ $activeRank->name }}{{ $activeRank->group ? ' (' . $activeRank->group . ')' : '' }}
						<button type="button" wire:click="$set('rank_id', '')" class="ml-1 text-indigo-500 hover:text-indigo-800" title="Hapus filter">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				@endif
				@if ($activeRole)
					<span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1 font-semibold text-violet-700 ring-1 ring-violet-600/20">
						<i class="fa-solid fa-shield-halved text-[10px]"></i>
						Role: {{ $activeRole->label ?? $activeRole->name }}
						<button type="button" wire:click="$set('role', '')" class="ml-1 text-violet-500 hover:text-violet-800" title="Hapus filter">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				@endif
			</div>
		@endif
	</div>

	{{-- Table --}}
	<div class="dash-enter rounded border border-slate-200 bg-white shadow-sm" wire:loading.class="opacity-60"
		wire:target="search,department_id,partai,position_id,rank_id,role">
		<div class="overflow-x-clip">
			<table class="table-stack w-full text-left table-fixed">
				<thead
					class="sticky top-13 lg:top-16 z-10 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 shadow-sm">
					<tr>
						<th class="py-3 px-4 w-12 text-center">No.</th>
						<th class="py-3 px-4 w-[22%]">Pegawai</th>
						@if (auth()->user()->hasRole('super_admin'))
							<th class="py-3 px-4 w-[20%]">Instansi</th>
						@endif
						@if ($isDprd)
							<th class="py-3 px-4">Jabatan DPRD</th>
							<th class="py-3 px-4 w-40">Partai / Fraksi</th>
						@else
							<th class="py-3 px-4">Jabatan</th>
							<th class="py-3 px-4 w-32">Pangkat / Gol.</th>
						@endif
						<th class="py-3 px-4 w-28">Role</th>
						<th class="py-3 px-4 w-28 text-center">Status</th>
						<th class="py-3 px-4 w-36 text-right">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 text-slate-700">
					@php $lastDeptId = null; @endphp
					@forelse($users as $i => $user)
						@php
							$deptId = $user->department_id;
							$depth = $deptDepthMap[$deptId] ?? 0;
							$indent = $depth * 16;
							$colCount = auth()->user()->hasRole('super_admin') ? 8 : 7;

							// Monogram inisial (1–2 huruf) dari nama — hindari request avatar eksternal per baris.
							$nameParts = preg_split('/\s+/', trim($user->name ?? ''), -1, PREG_SPLIT_NO_EMPTY);
							$initials = strtoupper(mb_substr($nameParts[0] ?? '?', 0, 1)
								. (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
						@endphp

						{{-- Department group header row when department changes --}}
						@if ($deptId !== $lastDeptId)
							@php $lastDeptId = $deptId; @endphp
							<tr class="stack-group bg-slate-50/80 border-y border-slate-100">
								<td colspan="{{ $colCount }}" class="py-1.5 px-4">
									<div class="flex items-center gap-2" style="padding-left: {{ $indent }}px">
										@if ($depth > 0)
											<i class="fa-solid fa-sitemap text-[9px] text-slate-500"></i>
										@else
											<i class="fa-solid fa-building-columns text-[9px] text-primary-500"></i>
										@endif
										<span
											class="text-[10px] font-bold uppercase tracking-widest {{ $depth === 0 ? 'text-primary-700' : ($depth === 1 ? 'text-slate-600' : 'text-slate-500') }}">
											{{ $user->department?->name ?? '-' }}
										</span>
									</div>
								</td>
							</tr>
						@endif

						<tr class="hover:bg-slate-50/50 transition-colors" wire:key="user-{{ $user->id }}">
							<td class="stack-hide py-3 px-4 text-center text-xs text-slate-500">{{ $users->firstItem() + $i }}.</td>

							{{-- Pegawai dengan indentasi berdasarkan kedalaman departemen --}}
							<td data-label="Pegawai" class="py-2.5 px-4">
								<div class="flex items-center gap-3" style="padding-left: {{ $indent }}px">
									<span
										class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-[11px] font-bold text-primary-700 ring-1 ring-primary-100"
										aria-hidden="true">{{ $initials }}</span>
									<div class="min-w-0">
										<p class="text-sm font-semibold leading-snug text-slate-900 wrap-break-word">{{ $user->name }}</p>
										<div class="flex flex-wrap items-center gap-x-2 text-[12px] text-slate-500">
											<span class="break-all"><i class="fa-solid fa-at text-[10px] mr-0.5"></i>{{ $user->username ?? '-' }}</span>
										</div>
									</div>
								</div>
							</td>

							@if (auth()->user()->hasRole('super_admin'))
								<td data-label="Instansi" class="py-3 px-4 text-sm text-slate-600 wrap-break-word">{{ $user->department?->name ?? '-' }}</td>
							@endif

							@if ($isDprd)
								<td data-label="Jabatan DPRD" class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->dprd_jabatan ?? '-' }}</div>
								</td>

								<td data-label="Partai / Fraksi" class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->partai ?? '-' }}</div>
								</td>
							@else
								<td data-label="Jabatan" class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->position?->name ?? '-' }}</div>
								</td>

								<td data-label="Pangkat / Gol." class="py-3 px-4">
									@if ($user->rank)
										<p class="text-[13px] text-slate-800">{{ $user->rank->name }}</p>
										<p class="text-xs text-slate-500">{{ $user->rank->group }}</p>
									@else
										<span class="text-sm text-slate-500">-</span>
									@endif
								</td>
							@endif

							<td data-label="Role" class="py-3 px-4">
								<div class="flex flex-wrap gap-1">
									@foreach ($user->roles as $role)
										<x-ui.badge :color="$role->color ?? 'slate'" class="whitespace-nowrap">{{ $role->label }}</x-ui.badge>
									@endforeach
								</div>
							</td>

							<td data-label="Status" class="py-3 px-4 text-center">
								@if ($user->is_active)
									<span
										class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
										<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
									</span>
								@else
									<span
										class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">
										<span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
									</span>
								@endif
							</td>

							<td data-label="Aksi" class="py-3 px-4 text-right">
								<div class="flex items-center justify-end gap-1">
									{{-- Toggle Status --}}
									<button type="button" wire:click="toggleActive({{ $user->id }})"
										title="{{ $user->is_active ? 'Nonaktifkan Pegawai' : 'Aktifkan Pegawai' }}"
										class="rounded p-1.5 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 {{ $user->is_active ? 'text-slate-500 hover:bg-slate-100 hover:text-rose-600' : 'text-slate-500 hover:bg-slate-100 hover:text-emerald-600' }}">
										<i class="fa-solid fa-power-off"></i>
									</button>

									{{-- View --}}
									<a wire:navigate href="{{ route('master.users.show', array_filter(['user' => $user, 'type' => $type])) }}"
										class="rounded p-1.5 text-slate-500 transition-colors hover:bg-primary-50 hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40"
										title="Detail Pegawai">
										<i class="fa-solid fa-eye"></i>
									</a>

									{{-- Edit --}}
									<a wire:navigate href="{{ route('master.users.edit', array_filter(['user' => $user, 'type' => $type])) }}"
										class="rounded p-1.5 text-slate-500 transition-colors hover:bg-amber-50 hover:text-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40"
										title="Edit Data">
										<i class="fa-solid fa-pen-to-square"></i>
									</a>

									{{-- Delete --}}
									<button type="button" wire:click="deleteUser({{ $user->id }})"
										wire:confirm="Yakin ingin menghapus pegawai ini secara permanen?"
										class="rounded p-1.5 text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40"
										title="Hapus Data">
										<i class="fa-solid fa-trash-can"></i>
									</button>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ auth()->user()->hasRole('super_admin') ? '8' : '7' }}" class="px-4 py-16">
								<div class="mx-auto flex max-w-sm flex-col items-center text-center">
									<div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
										<i class="fa-solid fa-folder-open text-xl"></i>
									</div>

									@if ($hasActiveFilters)
										<p class="mt-4 text-sm font-semibold text-slate-700">Tidak ada pegawai yang cocok</p>
										<p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci pencarian atau hapus sebagian filter yang aktif.</p>
										<x-ui.button wire:click="resetFilters" type="button" variant="secondary" size="sm" class="mt-4">
											<i class="fa-solid fa-rotate-right text-xs"></i> Reset filter
										</x-ui.button>
									@else
										<p class="mt-4 text-sm font-semibold text-slate-700">
											Belum ada data {{ $isDprd ? 'anggota DPRD' : 'pegawai' }}
										</p>
										<p class="mt-1 text-xs text-slate-500">
											Mulai dengan menambahkan {{ $isDprd ? 'anggota DPRD' : 'pegawai' }} pertama ke sistem.
										</p>
										<x-ui.button href="{{ route('master.users.create', array_filter(['type' => $type])) }}"
											variant="primary" size="sm" class="mt-4">
											<i class="fa-solid fa-plus text-xs"></i> {{ $isDprd ? 'Tambah Anggota DPRD' : 'Tambah Pegawai' }}
										</x-ui.button>
									@endif
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{-- Pagination --}}
		@if ($users->hasPages())
			<div class="border-t border-slate-200 bg-slate-50/50 px-4 py-3">
				{{ $users->links() }}
			</div>
		@endif
	</div>
</div>
