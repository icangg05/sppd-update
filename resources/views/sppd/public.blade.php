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
		<div class="dash-enter relative mb-6 overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 shadow-sm">
			{{-- Garis aksen atas (ornamen). --}}
			<div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-primary-600 via-primary-400 to-transparent"></div>
			{{-- Watermark institusional (tipis). --}}
			<i class="fa-solid fa-plane-departure pointer-events-none absolute -right-3 -top-3 text-8xl text-primary-500/6" aria-hidden="true"></i>
			{{-- Ornamen titik-titik (dot grid). --}}
			<div class="pointer-events-none absolute right-28 top-4 hidden h-14 w-28 sm:block"
				style="background-image: radial-gradient(rgba(30,128,198,.22) 1px, transparent 1px); background-size: 9px 9px;"
				aria-hidden="true"></div>

			<div class="relative flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
				<div class="min-w-0 leading-tight">
					<span class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
						<i class="fa-solid fa-file-lines text-[10px]"></i> {{ $sppd->category?->name ?? 'Perjalanan Dinas' }}
					</span>
					<h1 class="text-2xl font-bold tracking-tight text-slate-800">Detail Surat Perjalanan Dinas</h1>
					<p class="mt-1 flex items-center gap-1.5 font-mono text-sm text-slate-500">
						<i class="fa-solid fa-hashtag text-xs text-primary-500"></i>
						{{ $sppd->document_number ?? 'Belum memiliki nomor dokumen' }}
					</p>
					<p class="mt-1 text-sm text-slate-500">Informasi resmi perjalanan dinas — dapat dilihat oleh publik (read-only).</p>
				</div>
				<x-ui.badge :status="$sppd->status->value" class="inline-block w-fit shrink-0 rounded px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
					{{ $sppd->status->label() }}
				</x-ui.badge>
			</div>
		</div>

		<div class="space-y-6">

			{{-- Informasi Perjalanan --}}
			<div class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md before:absolute before:inset-x-0 before:top-0 before:z-10 before:h-0.5 before:bg-linear-to-r before:from-primary-400 before:via-primary-300 before:to-transparent">
				<div class="relative border-b border-slate-100 bg-linear-to-r from-slate-50 via-slate-50/60 to-transparent px-5 py-3.5">
					<h3 class="flex items-center gap-2.5 text-sm font-bold tracking-wide text-slate-700">
						<span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-linear-to-br from-primary-50 to-primary-100 text-primary-600 ring-1 ring-inset ring-primary-200/60 shadow-2xs"><i class="fa-solid fa-address-card text-[11px]"></i></span> Informasi Perjalanan
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
						<p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
							<i class="fa-solid fa-bullseye text-[11px] text-primary-500"></i> Maksud Perjalanan
						</p>
						<p class="rounded border border-slate-200 border-l-2 border-l-primary-400 bg-slate-50 p-3 text-sm font-medium leading-relaxed text-slate-700">{{ $sppd->purpose }}</p>
					</div>
					@if ($sppd->problem)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
								<i class="fa-solid fa-circle-question text-[11px] text-primary-500"></i> Persoalan
							</p>
							<p class="rounded border border-slate-200 border-l-2 border-l-primary-400 bg-slate-50 p-3 text-sm font-medium leading-relaxed text-slate-700">{{ $sppd->problem }}</p>
						</div>
					@endif
					@if ($sppd->facts)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
								<i class="fa-solid fa-clipboard-list text-[11px] text-primary-500"></i> Fakta-Fakta Yang Mempengaruhi
							</p>
							<p class="rounded border border-slate-200 border-l-2 border-l-primary-400 bg-slate-50 p-3 text-sm font-medium leading-relaxed text-slate-700">{{ $sppd->facts }}</p>
						</div>
					@endif
					@if ($sppd->analysis)
						<div class="border-t border-slate-100 pt-3 sm:col-span-2">
							<p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
								<i class="fa-solid fa-magnifying-glass-chart text-[11px] text-primary-500"></i> Analisis
							</p>
							<p class="rounded border border-slate-200 border-l-2 border-l-primary-400 bg-slate-50 p-3 text-sm font-medium leading-relaxed text-slate-700">{{ $sppd->analysis }}</p>
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
				<div class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md before:absolute before:inset-x-0 before:top-0 before:z-10 before:h-0.5 before:bg-linear-to-r before:from-primary-400 before:via-primary-300 before:to-transparent">
					<div class="relative border-b border-slate-100 bg-linear-to-r from-slate-50 via-slate-50/60 to-transparent px-5 py-3.5">
						<h3 class="flex items-center gap-2.5 text-sm font-bold tracking-wide text-slate-700">
							<span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-linear-to-br from-primary-50 to-primary-100 text-primary-600 ring-1 ring-inset ring-primary-200/60 shadow-2xs"><i class="fa-solid fa-money-check-dollar text-[11px]"></i></span> Pembebanan Anggaran
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
				<div class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md before:absolute before:inset-x-0 before:top-0 before:z-10 before:h-0.5 before:bg-linear-to-r before:from-primary-400 before:via-primary-300 before:to-transparent">
					<div class="relative border-b border-slate-100 bg-linear-to-r from-slate-50 via-slate-50/60 to-transparent px-5 py-3.5">
						<h3 class="flex items-center gap-2.5 text-sm font-bold tracking-wide text-slate-700">
							<span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-linear-to-br from-primary-50 to-primary-100 text-primary-600 ring-1 ring-inset ring-primary-200/60 shadow-2xs"><i class="fa-solid fa-map-location-dot text-[11px]"></i></span> Lokasi Tujuan
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
				<div class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md before:absolute before:inset-x-0 before:top-0 before:z-10 before:h-0.5 before:bg-linear-to-r before:from-primary-400 before:via-primary-300 before:to-transparent">
					<div class="relative border-b border-slate-100 bg-linear-to-r from-slate-50 via-slate-50/60 to-transparent px-5 py-3.5">
						<h3 class="flex items-center gap-2.5 text-sm font-bold tracking-wide text-slate-700">
							<span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-linear-to-br from-primary-50 to-primary-100 text-primary-600 ring-1 ring-inset ring-primary-200/60 shadow-2xs"><i class="fa-solid fa-users text-[11px]"></i></span> Daftar Pengikut
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
