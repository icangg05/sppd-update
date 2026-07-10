<!doctype html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<meta name="robots" content="noindex, nofollow">
	<title>Detail SPPD {{ $sppd->document_number ? '- ' . $sppd->document_number : '' }} | {{ config('app.name') }}</title>
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	@vite(['resources/css/app.css'])
	<style>
		body {
			font-family: 'Poppins', sans-serif;
		}
	</style>
</head>

<body class="bg-slate-100 text-slate-900">
	<div class="mx-auto max-w-4xl px-4 py-6 sm:py-10">

		{{-- Header --}}
		<div class="mb-6 flex items-center gap-3">
			<img src="{{ asset('img/logo-sppd.png') }}" alt="logo" class="size-10">
			<div class="leading-tight">
				<p class="text-sm font-bold text-slate-800">SPPD PEMERINTAH KOTA KENDARI</p>
				<p class="text-xs text-slate-500">Informasi Publik Surat Perjalanan Dinas</p>
			</div>
		</div>

		{{-- Judul & Status --}}
		<div class="mb-6 flex flex-col gap-3 rounded border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
			<div class="leading-tight">
				<h1 class="text-lg font-bold text-slate-800">Detail Surat Perjalanan Dinas</h1>
				<p class="mt-1 font-mono text-sm text-slate-500">
					<i class="fa-solid fa-hashtag mr-1 text-xs text-slate-500"></i>
					{{ $sppd->document_number ?? 'Belum memiliki nomor dokumen' }}
				</p>
			</div>
			<x-ui.badge :status="$sppd->status->value" class="inline-block w-fit rounded px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
				{{ $sppd->status->label() }}
			</x-ui.badge>
		</div>

		<div class="space-y-6">

			{{-- Informasi Perjalanan --}}
			<div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600">
						<i class="fa-solid fa-address-card text-primary-600"></i> Informasi Perjalanan
					</h3>
				</div>
				<div class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2">
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Pelaksana</p>
						<p class="text-sm font-bold text-slate-800">{{ $sppd->user->name }}</p>
						<p class="font-mono text-xs text-slate-500">{{ $sppd->user->nip ?? '-' }}</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Instansi Pengusul</p>
						<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget?->department?->name ?? '-' }}</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Kategori Perjalanan</p>
						<p class="text-sm font-semibold text-slate-800">{{ $sppd->category?->name ?? '-' }}</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Domain Wilayah</p>
						<p class="text-sm font-semibold text-slate-800">
							<span class="rounded border border-primary-100 bg-primary-50 px-2 py-0.5 text-xs uppercase text-primary-700">{{ $sppd->domain->label() }}</span>
						</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Sifat Surat Dokumen</p>
						<p class="text-sm font-semibold text-slate-800">
							<span class="inline-block rounded border px-2 py-0.5 text-xs font-bold uppercase tracking-wide
								@if (strtolower($sppd->urgency) === 'segera') border-rose-100 bg-rose-50 text-rose-700
								@else border-slate-200 bg-slate-50 text-slate-700 @endif">
								{{ $sppd->urgency ?? 'Biasa' }}
							</span>
						</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Pelaksanaan</p>
						<p class="text-sm font-semibold text-slate-800">
							{{ $sppd->start_date->translatedFormat('d M Y') }}
							<i class="fa-solid fa-arrow-right mx-1 text-[10px] text-slate-500"></i>
							{{ $sppd->end_date->translatedFormat('d M Y') }}
						</p>
						<p class="mt-0.5 text-xs text-slate-500"><i class="fa-regular fa-clock"></i> Durasi: {{ $sppd->duration_days }} hari</p>
					</div>
					<div>
						<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Pembuat Dokumen</p>
						<p class="text-sm font-semibold text-slate-800">{{ $sppd->creator?->name ?? '-' }}</p>
					</div>
					<div class="border-t border-slate-100 pt-3 sm:col-span-2">
						<p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Maksud Perjalanan</p>
						<p class="text-sm font-medium leading-relaxed text-slate-800">{{ $sppd->purpose }}</p>
					</div>
					@if ($sppd->problem)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Persoalan</p>
							<p class="rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">{{ $sppd->problem }}</p>
						</div>
					@endif
					@if ($sppd->facts)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Fakta-Fakta Yang Mempengaruhi</p>
							<p class="rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">{{ $sppd->facts }}</p>
						</div>
					@endif
					@if ($sppd->analysis)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Analisis</p>
							<p class="rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">{{ $sppd->analysis }}</p>
						</div>
					@endif
					@if ($sppd->notes)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Catatan Tambahan</p>
							<p class="rounded border border-amber-200 bg-amber-50 p-3 text-sm leading-relaxed text-slate-600">{{ $sppd->notes }}</p>
						</div>
					@endif
				</div>
			</div>

			{{-- Pembebanan Anggaran --}}
			@if ($sppd->budget)
				<div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600">
							<i class="fa-solid fa-money-check-dollar text-primary-600"></i> Pembebanan Anggaran
						</h3>
					</div>
					<div class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2">
						<div>
							<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">No. Rekening Anggaran</p>
							<p class="font-mono text-sm font-semibold text-slate-800">{{ $sppd->budget->account_code ?? '-' }}</p>
						</div>
						<div>
							<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Program</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget->program ?? '-' }}</p>
						</div>
						<div class="sm:col-span-2">
							<p class="mb-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">Kegiatan</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget->activity ?? '-' }}</p>
						</div>
					</div>
				</div>
			@endif

			{{-- Lokasi Tujuan --}}
			@if ($sppd->destinations->count())
				<div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600">
							<i class="fa-solid fa-map-location-dot text-primary-600"></i> Lokasi Tujuan
						</h3>
					</div>
					<div class="space-y-3 p-5">
						@foreach ($sppd->destinations as $dest)
							<div class="flex items-start gap-3 rounded border border-slate-200 bg-slate-50 p-3">
								<div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
									<i class="fa-solid fa-location-dot text-sm"></i>
								</div>
								<div class="min-w-0 leading-tight">
									<p class="text-sm font-bold text-slate-800">
										{{ $dest->province->name }}{{ $dest->regency ? ', ' . $dest->regency->name : '' }}</p>
									@if ($dest->address)
										<p class="mt-1 text-xs text-slate-500">{{ $dest->address }}</p>
									@endif
								</div>
							</div>
						@endforeach
					</div>
				</div>
			@endif

			{{-- Daftar Pengikut --}}
			@if ($sppd->followers->count())
				<div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600">
							<i class="fa-solid fa-users text-primary-600"></i> Daftar Pengikut
						</h3>
					</div>
					<div class="flex flex-wrap gap-2.5 p-5">
						@foreach ($sppd->followers as $f)
							<div class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white px-2 py-1.5">
								<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-600 text-[10px] font-bold text-white">
									{{ strtoupper(substr($f->user->name, 0, 1)) }}
								</span>
								<div class="pr-1 leading-tight">
									<span class="block text-sm font-semibold text-slate-700">{{ $f->user->name }}</span>
									@if ($f->travel_position)
										<span class="mt-0.5 block text-[10px] font-bold uppercase tracking-wide text-indigo-600">
											<i class="fa-solid fa-id-badge mr-0.5"></i>{{ $f->travel_position }}
										</span>
									@endif
								</div>
							</div>
						@endforeach
					</div>
				</div>
			@endif

		</div>

		{{-- Footer --}}
		<div class="mt-8 border-t border-slate-200 pt-4 text-center text-xs text-slate-500">
			Halaman ini bersifat informatif (read-only) &middot; &copy; {{ date('Y') }} Pemerintah Kota Kendari
		</div>
	</div>
</body>

</html>
