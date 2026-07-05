<div class="p-1 space-y-6">
	@php $isDprd = $this->isDprd(); @endphp

	{{-- Header --}}
	<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
		<div>
			<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
				<i class="fa-solid fa-users-gear mr-2 text-cyan-600"></i>Data Pegawai
			</h1>
			<p class="mt-1 text-xs text-slate-500 font-medium">Kelola data pegawai dan hak akses pengguna sistem</p>
		</div>
		<a wire:navigate href="{{ route('master.users.create', array_filter(['type' => $type])) }}"
			class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
			<i class="fa-solid fa-plus"></i> {{ $isDprd ? 'Tambah Anggota DPRD' : 'Tambah Pegawai' }}
		</a>
	</div>

	{{-- Filters --}}
	<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
		<div class="flex flex-col sm:flex-row gap-3">

			{{-- Search Input with Icon --}}
			<div class="relative flex-1">
				<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
					<i class="fa-solid fa-magnifying-glass text-xs"></i>
				</div>
				<input type="text" wire:model.live.debounce.400ms="search"
					class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-9 text-sm focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition"
					placeholder="{{ $this->searchPlaceholder() }}">
				<div wire:loading wire:target="search"
					class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-cyan-500">
					<i class="fa-solid fa-spinner fa-spin text-xs"></i>
				</div>
			</div>

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
				<x-ui.button wire:click="resetFilters" type="button" variant="secondary"
					class="rounded-lg px-4 py-2 font-semibold text-slate-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
					:disabled="!$hasActiveFilters">
					<i class="fa-solid fa-rotate-right"></i> Reset
				</x-ui.button>
			</div>
		</div>

		{{-- Indikator filter dari halaman master (Data Jabatan / Data Pangkat / Kelola Role) --}}
		@if ($activePosition || $activeRank || $activeRole)
			<div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
				@if ($activePosition)
					<span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-50 px-3 py-1 font-semibold text-cyan-700 ring-1 ring-cyan-600/20">
						<i class="fa-solid fa-id-badge text-[10px]"></i>
						Jabatan: {{ $activePosition->name }}
						<button type="button" wire:click="$set('position_id', '')" class="ml-1 text-cyan-500 hover:text-cyan-800" title="Hapus filter">
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
	<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden" wire:loading.class="opacity-60"
		wire:target="search,department_id,partai,position_id,rank_id,role">
		<div class="overflow-x-auto custom-scrollbar">
			<table class="w-full text-left whitespace-nowrap">
				<thead
					class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
					<tr>
						<th class="py-3 px-4 w-12 text-center">No.</th>
						<th class="py-3 px-4">Pegawai</th>
						@if (auth()->user()->hasRole('super_admin'))
							<th class="py-3 px-4">Instansi</th>
						@endif
						@if ($isDprd)
							<th class="py-3 px-4">Jabatan DPRD</th>
							<th class="py-3 px-4">Partai / Fraksi</th>
						@else
							<th class="py-3 px-4">Jabatan</th>
							<th class="py-3 px-4">Pangkat / Gol.</th>
						@endif
						<th class="py-3 px-4">Role</th>
						<th class="py-3 px-4 text-center">Status</th>
						<th class="py-3 px-4 text-right">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 text-slate-700">
					@php $lastDeptId = null; @endphp
					@forelse($users as $i => $user)
						@php
							$deptId = $user->department_id;
							$depth = $deptDepthMap[$deptId] ?? 0;
							$indent = $depth * 16;
							$colCount = auth()->user()->hasRole('super_admin') ? 9 : 8;
						@endphp

						{{-- Department group header row when department changes --}}
						@if ($deptId !== $lastDeptId)
							@php $lastDeptId = $deptId; @endphp
							<tr class="bg-slate-50/80 border-y border-slate-100">
								<td colspan="{{ $colCount }}" class="py-1.5 px-4">
									<div class="flex items-center gap-2" style="padding-left: {{ $indent }}px">
										@if ($depth > 0)
											<i class="fa-solid fa-sitemap text-[9px] text-slate-400"></i>
										@else
											<i class="fa-solid fa-building-columns text-[9px] text-cyan-500"></i>
										@endif
										<span
											class="text-[10px] font-bold uppercase tracking-widest {{ $depth === 0 ? 'text-cyan-700' : ($depth === 1 ? 'text-slate-600' : 'text-slate-400') }}">
											{{ $user->department?->name ?? '-' }}
										</span>
									</div>
								</td>
							</tr>
						@endif

						<tr class="hover:bg-slate-50/50 transition-colors" wire:key="user-{{ $user->id }}">
							<td class="py-3 px-4 text-center text-xs text-slate-400">{{ $users->firstItem() + $i }}.</td>

							{{-- Pegawai dengan indentasi berdasarkan kedalaman departemen --}}
							<td class="py-2.5 px-4">
								<div style="padding-left: {{ $indent + 12 }}px">
									<p class="text-sm font-semibold text-slate-900 leading-snug">{{ $user->name }}</p>
									<div class="flex flex-wrap items-center gap-x-2 text-[12px] text-slate-500">
										<span><i class="fa-solid fa-at text-[10px] mr-0.5"></i>{{ $user->username ?? '-' }}</span>
									</div>
								</div>
							</td>

							@if (auth()->user()->hasRole('super_admin'))
								<td class="py-3 px-4 text-sm text-slate-600">{{ $user->department?->name ?? '-' }}</td>
							@endif

							@if ($isDprd)
								<td class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->dprd_jabatan ?? '-' }}</div>
								</td>

								<td class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->partai ?? '-' }}</div>
								</td>
							@else
								<td class="py-3 px-4">
									<div class="text-[13px] text-slate-600 text-wrap">{{ $user->position?->name ?? '-' }}</div>
								</td>

								<td class="py-3 px-4">
									@if ($user->rank)
										<p class="text-[13px] text-slate-800">{{ $user->rank->name }}</p>
										<p class="text-xs text-slate-500">{{ $user->rank->group }}</p>
									@else
										<span class="text-sm text-slate-400">-</span>
									@endif
								</td>
							@endif

							<td class="py-3 px-4">
								<div class="flex flex-wrap gap-1">
									@foreach ($user->roles as $role)
										<x-ui.badge :color="$role->color ?? 'slate'">{{ $role->label }}</x-ui.badge>
									@endforeach
								</div>
							</td>

							<td class="py-3 px-4 text-center">
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

							<td class="py-3 px-4 text-right">
								<div class="flex items-center justify-end gap-1">
									{{-- Toggle Status --}}
									<button type="button" wire:click="toggleActive({{ $user->id }})"
										title="{{ $user->is_active ? 'Nonaktifkan Pegawai' : 'Aktifkan Pegawai' }}"
										class="rounded p-1.5 text-xs font-medium transition-colors {{ $user->is_active ? 'text-slate-400 hover:bg-slate-100 hover:text-rose-600' : 'text-slate-400 hover:bg-slate-100 hover:text-emerald-600' }}">
										<i class="fa-solid fa-power-off"></i>
									</button>

									{{-- View --}}
									<a wire:navigate href="{{ route('master.users.show', array_filter(['user' => $user, 'type' => $type])) }}"
										class="rounded p-1.5 text-slate-400 hover:bg-cyan-50 hover:text-cyan-600 transition-colors"
										title="Detail Pegawai">
										<i class="fa-solid fa-eye"></i>
									</a>

									{{-- Edit --}}
									<a wire:navigate href="{{ route('master.users.edit', array_filter(['user' => $user, 'type' => $type])) }}"
										class="rounded p-1.5 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
										title="Edit Data">
										<i class="fa-solid fa-pen-to-square"></i>
									</a>

									{{-- Delete --}}
									<button type="button" wire:click="deleteUser({{ $user->id }})"
										wire:confirm="Yakin ingin menghapus pegawai ini secara permanen?"
										class="rounded p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
										title="Hapus Data">
										<i class="fa-solid fa-trash-can"></i>
									</button>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ auth()->user()->hasRole('super_admin') ? '9' : '8' }}" class="py-12 text-center">
								<div class="flex flex-col items-center justify-center text-slate-400">
									<i class="fa-solid fa-folder-open text-3xl mb-3 opacity-50"></i>
									<p class="text-sm font-medium">Belum ada data pegawai yang ditemukan</p>
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
