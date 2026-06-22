@extends('layouts.app')
@section('title', 'Kelola Role')
@section('page-title', 'Kelola Role')

@section('content')
	<div class="p-1 space-y-4">

		{{-- Header --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
			<div class="flex items-center gap-2.5">
				<div class="p-1.5 bg-violet-100 rounded text-violet-600">
					<i class="fa-solid fa-shield-halved text-base"></i>
				</div>
				<div>
					<h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Kelola Role</h1>
					<p class="text-[11px] text-slate-500 font-medium">Atur hak akses peran pengguna dalam sistem</p>
				</div>
			</div>

			<div class="flex items-center gap-2">
				<a wire:navigate href="{{ route('master.roles.create') }}"
					class="inline-flex items-center gap-1.5 rounded bg-violet-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-violet-200 transition hover:bg-violet-700 hover:shadow-lg">
					<i class="fa-solid fa-plus text-[10px]"></i>
					Tambah Role
				</a>
			</div>
		</div>

		{{-- Flash sukses ditangani toast global (lihat layouts.app) --}}
		@if ($errors->any())
			<div class="flex items-center gap-2 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
				<i class="fa-solid fa-circle-xmark"></i>
				{{ $errors->first() }}
			</div>
		@endif

		{{-- Table --}}
		<div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left whitespace-nowrap border-collapse">
					<thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
						<tr>
							<th class="py-2.5 px-3 w-10 text-center">No.</th>
							<th class="py-2.5 px-4">Nama Role</th>
							<th class="py-2.5 px-4">Label</th>
							<th class="py-2.5 px-4">Permissions</th>
							<th class="py-2.5 px-4">Pengguna</th>
							<th class="py-2.5 px-4 w-24 text-center">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
						@forelse ($roles as $i => $role)
							<tr class="hover:bg-slate-50/50 transition-colors">
								<td class="py-2.5 px-3 text-center text-slate-400 font-medium">{{ $i + 1 }}.</td>

								<td class="py-2.5 px-4">
									<code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-mono text-slate-700">{{ $role->name }}</code>
									@if ($role->name === 'super_admin')
										<span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1 py-0.5 text-[9px] font-black text-amber-700 uppercase">Protected</span>
									@endif
								</td>

								<td class="py-2.5 px-4 font-semibold text-slate-800">
									{{ $role->label ?? '-' }}
								</td>

								<td class="py-2.5 px-4">
									@if ($role->permissions->count() > 0)
										<div class="flex flex-wrap gap-1">
											@foreach ($role->permissions->take(5) as $perm)
												<span class="inline-flex items-center rounded bg-violet-50 px-1.5 py-0.5 text-[9px] font-bold text-violet-700 border border-violet-100">
													{{ $perm->name }}
												</span>
											@endforeach
											@if ($role->permissions->count() > 5)
												<span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 border border-slate-200">
													+{{ $role->permissions->count() - 5 }} lainnya
												</span>
											@endif
										</div>
									@else
										<span class="text-slate-400 italic text-[11px]">Tidak ada permission</span>
									@endif
								</td>

								<td class="py-2.5 px-4">
									<span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
										<i class="fa-solid fa-users text-[9px]"></i>
										{{ $role->users_count ?? $role->users()->count() }}
									</span>
								</td>

								<td class="py-2.5 px-4 text-center">
									<div class="flex items-center justify-center gap-1">
										<a wire:navigate href="{{ route('master.roles.edit', $role->id) }}"
											class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
											title="Edit">
											<i class="fa-solid fa-pen-to-square text-[10px]"></i>
										</a>

										@if ($role->name !== 'super_admin')
											<form action="{{ route('master.roles.destroy', $role->id) }}" method="POST" class="inline m-0"
												onsubmit="return confirm('Yakin ingin menghapus role ini? Semua pengguna dengan role ini akan kehilangan aksesnya.')">
												@csrf @method('DELETE')
												<button type="submit"
													class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
													title="Hapus">
													<i class="fa-solid fa-trash-can text-[10px]"></i>
												</button>
											</form>
										@endif
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="6" class="py-10 text-center text-slate-400">
									<div class="flex flex-col items-center justify-center gap-1.5">
										<i class="fa-solid fa-shield-halved text-2xl opacity-40"></i>
										<p class="font-medium">Belum ada role yang tersimpan</p>
									</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>

	</div>
@endsection
