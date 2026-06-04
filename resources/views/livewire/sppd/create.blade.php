<div class="flex flex-col gap-4 p-1">

	{{-- Header Halaman --}}
	<div class="leading-tight">
		<h1 class="text-lg font-bold text-slate-800">Buat SPPD Baru</h1>
		<p class="text-xs text-slate-500 mt-0.5">Tahap 1: Pilih Pelaksana & Validasi Alur Pengajuan</p>
	</div>

	{{-- Formulir Utama Tahap 1 --}}
	<form wire:submit.prevent="submit" id="form-step-1">
		<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">

			{{-- Sub Header Card --}}
			<div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/75 px-5 py-3.5">
				<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-2">
					<i class="fa-solid fa-paste text-cyan-600"></i>
					Tahap 1: Pelaksana & Estimasi Alur
				</h3>
				<span
					class="flex size-6 items-center justify-center rounded-full bg-cyan-600 text-xs font-bold text-white shadow-2xs">1</span>
			</div>

			{{-- Input Grid Pemilihan Pelaksana & Domain --}}
			<div class="p-5 grid grid-cols-1 gap-5 md:grid-cols-2">
				<div>
					<label for="user_id" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Pelaksana Perjalanan Dinas <span class="text-rose-500">*</span></label>
					<select wire:model.live="user_id" id="user_id" required
						class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500">
						<option value="">— Pilih Pegawai yang Berangkat —</option>
						@foreach ($users as $u)
							<option value="{{ $u->id }}">
								{{ $u->nip ? $u->nip . ' -' : '' }} {{ $u->name }}
							</option>
						@endforeach
					</select>
				</div>

				<div>
					<label for="domain" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Domain Perjalanan <span class="text-rose-500">*</span></label>
					<select wire:model.live="domain" id="domain" required
						class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500">
						<option value="dalam_daerah">Dalam Daerah</option>
						<option value="lddp">Luar Daerah Dalam Provinsi (LDDP)</option>
						<option value="ldlp">Luar Daerah Luar Provinsi (LDLP)</option>
					</select>
				</div>
			</div>

			{{-- Container Pratinjau Alur Alur Dokumen --}}
			@if ($user_id)
				<div id="workflow-preview" class="border-t border-slate-100 bg-slate-50/50 p-5 animate-fadeIn">
					<div class="flex items-center justify-between mb-4">
						<h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
							<i class="fa-solid fa-diagram-project text-slate-400"></i>
							Pratinjau Alur Persetujuan Dokumen
						</h4>
						<div>
							@if ($isComplete)
								<span class="inline-flex items-center gap-1 rounded-sm bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-emerald-700">
									<i class="fa-solid fa-circle-check text-xs"></i> Lengkap
								</span>
							@elseif ($errorMessage !== '')
								<span class="inline-block rounded-sm bg-red-50 border border-red-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-red-700">
									Tidak Lengkap
								</span>
							@else
								<span class="inline-block rounded-sm bg-slate-50 border border-slate-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-slate-700 animate-pulse">
									Memeriksa...
								</span>
							@endif
						</div>
					</div>

					{{-- Grid Langkah Alur --}}
					<div id="workflow-steps" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
						@forelse ($steps as $index => $step)
							@php
								$isFound = $step['status'] === 'found';
								$cardClass = $isFound ? 'bg-emerald-50/60 border-emerald-200 shadow-2xs' : 'bg-red-50 border-red-200 ring-1 ring-red-200';
								$badgeClass = $isFound ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white';
								$labelClass = $isFound ? 'text-emerald-600 font-bold' : 'text-red-500 font-bold';
								$nameClass = $isFound ? 'text-emerald-900 font-bold' : 'text-red-700 italic font-medium';
							@endphp

							<div class="flex items-center gap-3 rounded border p-3 transition-all {{ $cardClass }}">
								<div class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold shadow-2xs {{ $badgeClass }}">
									{{ $index + 1 }}
								</div>
								<div class="flex-1 min-w-0 leading-tight">
									<p class="text-xs uppercase tracking-wider {{ $labelClass }}">
										{{ $step['role_label'] }}
									</p>
									<p class="text-sm mt-0.5 truncate {{ $nameClass }}" title="{{ $step['approver_name'] }}">
										{{ $step['approver_name'] }}
									</p>
								</div>
							</div>
						@empty
							@if ($errorMessage === '')
								<div class="col-span-full py-6 text-center text-sm font-medium text-slate-400 italic">
									<i class="fa-solid fa-circle-notch fa-spin mr-2 text-cyan-600"></i>Memvalidasi alur instansi...
								</div>
							@else
								<div class="col-span-full py-6 text-center text-sm font-medium text-slate-400 italic">
									<i class="fa-solid fa-triangle-exclamation mr-2 text-amber-500"></i>Alur persetujuan tidak tersedia.
								</div>
							@endif
						@endforelse
					</div>

					{{-- Pesan Kesalahan / Validasi Alur Instansi --}}
					@if ($errorMessage !== '')
						<div id="workflow-error-msg" class="mt-4 rounded border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
							<div class="flex gap-2">
								<i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5"></i>
								<div>
									<span class="font-bold">Peringatan Validasi:</span>
									<span>{{ $errorMessage }}</span>
								</div>
							</div>
						</div>
					@endif

					{{-- Tombol Lanjutkan --}}
					<div class="mt-6 pt-4 border-t border-slate-200 flex justify-center">
						<button type="submit" @disabled(!$isComplete)
							class="inline-flex items-center gap-2 rounded bg-cyan-600 px-12 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none">
							<span>Lanjut Isi Detail SPPD</span>
							<i class="fa-solid fa-arrow-right text-xs"></i>
						</button>
					</div>
				</div>
			@endif

		</div>
	</form>
</div>
