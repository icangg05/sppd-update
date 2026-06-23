<div>
	<div class="p-1 space-y-4">

		{{-- Header --}}
		<div class="flex items-center gap-2.5 border-b border-slate-200 pb-3">
			<a wire:navigate href="{{ route('master.roles.index') }}"
				class="rounded border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
				<i class="fa-solid fa-chevron-left text-[10px]"></i>
			</a>
			<div class="p-1.5 bg-violet-100 rounded text-violet-600">
				<i class="fa-solid fa-shield-halved text-base"></i>
			</div>
			<div>
				<h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">
					{{ $isEdit ? 'Edit Role' : 'Tambah Role' }}
				</h1>
				<p class="text-[11px] text-slate-500 font-medium">
					{{ $isEdit ? 'Ubah label atau hak akses untuk role ini' : 'Buat peran pengguna baru dalam sistem' }}
				</p>
			</div>
		</div>

		{{-- Validation Errors --}}
		@if ($errors->any())
			<div class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 space-y-0.5">
				@foreach ($errors->all() as $err)
					<p><i class="fa-solid fa-circle-xmark mr-1"></i>{{ $err }}</p>
				@endforeach
			</div>
		@endif

		<form wire:submit.prevent="save" class="space-y-4">

			<div class="bg-white rounded border border-slate-200 shadow-sm p-5 space-y-4">
				<h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 border-b border-slate-100 pb-2">
					Informasi Role
				</h2>

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

					{{-- Nama Role --}}
					<div class="space-y-1">
						<label for="role-name" class="block text-xs font-semibold text-slate-600">
							Nama Role @if (!$isEdit)<span class="text-rose-500">*</span>@endif
						</label>

						@if ($isEdit)
							<div class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-1.5">
								<code class="text-[11px] font-mono text-slate-600">{{ $name }}</code>
								@if ($name === 'super_admin')
									<span class="inline-flex items-center rounded bg-amber-100 px-1 py-0.5 text-[9px] font-black text-amber-700 uppercase">Protected</span>
								@endif
							</div>
							<p class="text-[11px] text-slate-400">Nama role tidak dapat diubah</p>
						@else
							<input id="role-name" type="text" wire:model="name"
								placeholder="contoh: kepala_bagian"
								class="w-full rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 shadow-sm focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-300 @error('name') border-rose-400 @enderror">
							<p class="text-[11px] text-slate-400">Gunakan huruf kecil dan underscore, tanpa spasi</p>
							@error('name')
								<p class="text-[11px] text-rose-500">{{ $message }}</p>
							@enderror
						@endif
					</div>

					{{-- Label --}}
					<div class="space-y-1">
						<label for="role-label" class="block text-xs font-semibold text-slate-600">
							Label / Nama Tampil <span class="text-rose-500">*</span>
						</label>
						<input id="role-label" type="text" wire:model="label"
							placeholder="contoh: Kepala Bagian"
							class="w-full rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 shadow-sm focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-300 @error('label') border-rose-400 @enderror">
						@error('label')
							<p class="text-[11px] text-rose-500">{{ $message }}</p>
						@enderror
					</div>
				</div>

				{{-- Warna Badge --}}
				@php
					$swatch = [
						'slate' => 'bg-slate-500',
						'red' => 'bg-red-500',
						'orange' => 'bg-orange-500',
						'amber' => 'bg-amber-500',
						'yellow' => 'bg-yellow-500',
						'lime' => 'bg-lime-500',
						'green' => 'bg-green-500',
						'emerald' => 'bg-emerald-500',
						'teal' => 'bg-teal-500',
						'cyan' => 'bg-cyan-500',
						'sky' => 'bg-sky-500',
						'blue' => 'bg-blue-500',
						'indigo' => 'bg-indigo-500',
						'violet' => 'bg-violet-500',
						'purple' => 'bg-purple-500',
						'fuchsia' => 'bg-fuchsia-500',
						'pink' => 'bg-pink-500',
						'rose' => 'bg-rose-500',
					];
				@endphp
				<div class="space-y-2">
					<div class="flex items-center justify-between">
						<label class="block text-xs font-semibold text-slate-600">Warna Badge</label>
						<x-ui.badge :color="$color">{{ $label !== '' ? $label : 'Pratinjau' }}</x-ui.badge>
					</div>
					<div class="flex flex-wrap gap-2 rounded border border-slate-200 bg-slate-50 p-3">
						@foreach (\App\Support\BadgeColor::PALETTE as $token)
							<button type="button" wire:click="$set('color', '{{ $token }}')"
								title="{{ ucfirst($token) }}"
								@class([
									'size-6 rounded-full ' . ($swatch[$token] ?? 'bg-slate-500'),
									'ring-2 ring-offset-2 ring-slate-800' => $color === $token,
									'ring-1 ring-inset ring-black/10' => $color !== $token,
								])></button>
						@endforeach
					</div>
					@error('color')
						<p class="text-[11px] text-rose-500">{{ $message }}</p>
					@enderror
				</div>

				{{-- Permissions --}}
				<div class="space-y-2">
					<div class="flex items-center justify-between">
						<label class="block text-xs font-semibold text-slate-600">Permissions</label>
						@if ($permissions->count() > 0)
							<div class="flex items-center gap-2">
								<button type="button" wire:click="toggleAll(true)"
									class="text-[11px] text-violet-600 hover:underline font-medium">Pilih Semua</button>
								<span class="text-slate-300">|</span>
								<button type="button" wire:click="toggleAll(false)"
									class="text-[11px] text-slate-400 hover:underline font-medium">Hapus Semua</button>
							</div>
						@endif
					</div>

					@if ($permissions->count() > 0)
						<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 rounded border border-slate-200 bg-slate-50 p-3">
							@foreach ($permissions as $perm)
								<label class="flex items-center gap-2 cursor-pointer group">
									<input type="checkbox"
										wire:model="selectedPermissions"
										value="{{ $perm->name }}"
										class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
									<span class="text-[11px] text-slate-600 group-hover:text-slate-900 transition-colors leading-tight">
										{{ $perm->name }}
									</span>
								</label>
							@endforeach
						</div>
					@else
						<p class="text-[11px] text-slate-400 italic">
							Belum ada permission yang tersedia dalam sistem.
						</p>
					@endif
				</div>
			</div>

			<div class="flex items-center justify-end gap-2">
				<a wire:navigate href="{{ route('master.roles.index') }}"
					class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition hover:bg-slate-50">
					Batal
				</a>
				<button type="submit" wire:loading.attr="disabled"
					class="inline-flex items-center gap-1.5 rounded bg-violet-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-violet-200 transition hover:bg-violet-700 disabled:opacity-60">
					<span wire:loading wire:target="save">
						<i class="fa-solid fa-circle-notch fa-spin text-[10px]"></i>
					</span>
					<span wire:loading.remove wire:target="save">
						<i class="fa-solid fa-floppy-disk text-[10px]"></i>
					</span>
					{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Role' }}
				</button>
			</div>

		</form>
	</div>
</div>
