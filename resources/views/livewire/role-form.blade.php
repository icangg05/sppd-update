<div>
	<div class="p-1 space-y-4">

		{{-- Header (title card — aksen violet identitas Role) --}}
		<div
			class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-violet-50/50 px-5 py-4 shadow-sm">
			{{-- Watermark institusional (tipis, hanya karakter). --}}
			<i class="fa-solid fa-shield-halved pointer-events-none absolute -right-3 -top-4 text-8xl text-violet-500/6"
				aria-hidden="true"></i>

			<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
				<div class="min-w-0 leading-tight">
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-violet-700 ring-1 ring-inset ring-violet-600/15">
						<i class="fa-solid fa-user-shield text-[9px]"></i>
						{{ $isEdit ? 'Perbarui Role' : 'Role Baru' }}
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">
						{{ $isEdit ? 'Edit Role' : 'Tambah Role' }}
					</h1>
					<p class="mt-1 text-xs text-slate-500">
						{{ $isEdit ? 'Ubah label atau hak akses untuk role ini' : 'Buat peran pengguna baru dalam sistem' }}
					</p>
				</div>

				<x-ui.button href="{{ route('master.roles.index') }}" variant="secondary" class="shrink-0 font-bold">
					<x-slot name="icon"><i class="fa-solid fa-arrow-left text-[10px]"></i></x-slot>
					Kembali
				</x-ui.button>
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

			<div class="dash-enter bg-white rounded border border-slate-200 shadow-sm p-5 space-y-4">
				<h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 border-b border-slate-100 pb-2">
					Informasi Role
				</h2>

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

					{{-- Nama Role --}}
					@if ($isEdit)
						<div class="space-y-1">
							<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Nama Role</label>
							<div class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2">
								<code class="text-[11px] font-mono text-slate-600">{{ $name }}</code>
								@if ($name === 'super_admin')
									<span class="inline-flex items-center rounded bg-amber-100 px-1 py-0.5 text-[9px] font-black text-amber-700 uppercase">Protected</span>
								@endif
							</div>
							<p class="text-[11px] text-slate-500">Nama role tidak dapat diubah</p>
						</div>
					@else
						<x-form.input wire:model="name" name="name" label="Nama Role" required
							placeholder="contoh: kepala_bagian"
							hint="Gunakan huruf kecil dan underscore, tanpa spasi" />
					@endif

					{{-- Label --}}
					<x-form.input wire:model="label" name="label" label="Label / Nama Tampil" required
						placeholder="contoh: Kepala Bagian" />
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
						'cyan' => 'bg-primary-500',
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
									class="text-[11px] text-slate-500 hover:underline font-medium">Hapus Semua</button>
							</div>
						@endif
					</div>

					@if ($permissions->count() > 0)
						<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 rounded border border-slate-200 bg-slate-50 p-3">
							@foreach ($permissions as $perm)
								<x-form.checkbox wire:model="selectedPermissions" :value="$perm->name" :label="$perm->name"
									wrapper-class="min-w-0" />
							@endforeach
						</div>
					@else
						<p class="text-[11px] text-slate-500 italic">
							Belum ada permission yang tersedia dalam sistem.
						</p>
					@endif
				</div>
			</div>

			<div class="flex items-center justify-end gap-2">
				<x-ui.button href="{{ route('master.roles.index') }}" variant="secondary" class="font-bold">
					Batal
				</x-ui.button>
				<x-ui.button type="submit" variant="violet" class="font-bold" wire:target="save">
					<x-slot name="icon"><i class="fa-solid fa-floppy-disk text-[10px]"></i></x-slot>
					{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Role' }}
				</x-ui.button>
			</div>

		</form>
	</div>
</div>
