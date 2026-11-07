<!doctype html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<meta name="robots" content="noindex, nofollow">
	<title>Detail SPPD {{ $sppd->document_number ? '| ' . $sppd->document_number : '' }} | {{ config('app.name') }}</title>
	<link rel="apple-touch-icon" href="{{ asset('img/logo-sppd.png') }}">
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|ibm-plex-mono:400,500,600,700" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	@vite(['resources/css/app.css'])
</head>

{{-- Selaras dengan halaman Detail Perjalanan (livewire/sppd/show): latar biru muda, bar atas biru,
     kartu datar ber-header biru solid, label kalimat biasa. Data yang tampil tetap versi publik
     (tanpa pagu/realisasi anggaran & alur persetujuan). --}}
@php
	$card = 'dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm';
	$cardTitle = 'flex items-center gap-2 bg-primary-700 px-5 py-3 text-sm font-bold text-white';
	$label = 'text-xs font-medium text-slate-500';
	$value = 'mt-0.5 text-sm font-semibold text-slate-800';
	$fieldGrid = 'grid grid-cols-1 gap-px bg-slate-200 @lg:grid-cols-2';
	$fieldCell = 'bg-white px-5 py-2.5';
	$dateRange = $sppd->start_date->isSameDay($sppd->end_date)
	    ? $sppd->start_date->translatedFormat('d M Y')
	    : $sppd->start_date->translatedFormat('d M Y') . ' s/d ' . $sppd->end_date->translatedFormat('d M Y');
	$narratives = array_filter([
	    'Maksud perjalanan' => $sppd->purpose,
	    'Persoalan' => $sppd->problem,
	    'Fakta-fakta yang mempengaruhi' => $sppd->facts,
	    'Analisis' => $sppd->analysis,
	]);
@endphp

<body class="flex min-h-dvh flex-col bg-blue-50 text-slate-900 antialiased">

	{{-- Bar atas: identitas aplikasi, senada header aplikasi. --}}
	<header class="border-b border-primary-600/50 bg-primary-700 text-white shadow-sm">
		<div class="mx-auto flex h-14 max-w-4xl items-center gap-3 px-4">
			<span class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-600/50 ring-1 ring-inset ring-white/15">
				<img src="{{ asset('img/logo-sppd.png') }}" alt="Logo SPPD Kota Kendari" class="size-7">
			</span>
			<div class="min-w-0 leading-tight">
				<p class="truncate text-sm font-bold tracking-wide">SPPD Pemerintah Kota Kendari</p>
				<p class="truncate text-xs font-medium text-primary-200">Informasi publik surat perjalanan dinas</p>
			</div>
		</div>
	</header>

	<main class="@container mx-auto w-full max-w-4xl flex-1 space-y-5 px-4 py-5 sm:py-6">

		{{-- Kepala halaman: identitas SPPD + status di kiri, QR halaman ini di kanan atas. --}}
		<div class="dash-enter flex items-start justify-between gap-4 rounded border border-slate-200 bg-white px-5 py-4 shadow-sm">
			<div class="min-w-0">
				<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Detail Surat Perjalanan Dinas</h1>
					<x-ui.badge :status="$sppd->status->value" class="rounded px-2 py-0.5 text-xs font-bold">
						{{ $sppd->status->label() }}
					</x-ui.badge>
				</div>
				<p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
					<span class="inline-flex items-center gap-1.5">
						<i class="fa-solid fa-user text-xs text-slate-400"></i>{{ $sppd->user->name }}
					</span>
					<span class="inline-flex items-center gap-1.5">
						<i class="fa-regular fa-calendar text-xs text-slate-400"></i>{{ $dateRange }}
					</span>
					{{-- Nomor dokumen hanya dipakai Inspektorat (diinput di form SPPD). --}}
					@if ($sppd->showsDocumentNumber())
						<span class="inline-flex items-center gap-1.5 font-mono">
							<i class="fa-solid fa-hashtag text-xs text-slate-400"></i>{{ $sppd->document_number ?: 'Belum memiliki nomor dokumen' }}
						</span>
					@endif
				</p>
				<p class="mt-2 text-xs text-slate-500">Informasi resmi perjalanan dinas yang dapat dilihat publik (hanya baca).</p>
			</div>

			<figure class="shrink-0 text-center">
				<div class="size-20 rounded border border-slate-200 bg-white p-1.5 sm:size-28 [&>svg]:size-full" role="img"
					aria-label="Kode QR halaman publik SPPD ini">
					{!! $qrSvg !!}
				</div>
				<figcaption class="mt-1 text-[11px] leading-tight text-slate-500">Pindai untuk<br class="sm:hidden"> membuka</figcaption>
			</figure>
		</div>

		{{-- Informasi Perjalanan --}}
		<section class="{{ $card }}">
			<h2 class="{{ $cardTitle }}">
				<i class="fa-solid fa-address-card text-xs text-primary-100"></i> Informasi Perjalanan
			</h2>
			<dl class="{{ $fieldGrid }}">
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Pelaksana</dt>
					<dd class="{{ $value }}">{{ $sppd->user->name }}</dd>
					<dd class="font-mono text-xs text-slate-500">{{ $sppd->user->nip ?? '-' }}</dd>
				</div>
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Tanggal pelaksanaan</dt>
					<dd class="{{ $value }}">{{ $dateRange }}</dd>
					<dd class="text-xs text-slate-500">{{ $sppd->duration_days }} hari</dd>
				</div>
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Kategori perjalanan</dt>
					<dd class="{{ $value }}">{{ $sppd->category?->name ?? '-' }}</dd>
				</div>
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Domain wilayah</dt>
					<dd class="{{ $value }}">{{ $sppd->domain->label() }}</dd>
				</div>
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Sifat surat</dt>
					<dd class="mt-0.5">
						@if (strtolower((string) $sppd->urgency) === 'segera')
							<span class="inline-block rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700">Segera</span>
						@else
							<span class="text-sm font-semibold text-slate-800">{{ $sppd->urgency ?? 'Biasa' }}</span>
						@endif
					</dd>
				</div>
				<div class="{{ $fieldCell }}">
					<dt class="{{ $label }}">Pembuat dokumen</dt>
					<dd class="{{ $value }}">{{ $sppd->creator?->name ?? '-' }}</dd>
				</div>
				{{-- Nama instansi bisa panjang, jadi selebar kartu dan menutup baris ganjil. --}}
				<div class="{{ $fieldCell }} @lg:col-span-2">
					<dt class="{{ $label }}">Instansi pengusul</dt>
					<dd class="{{ $value }}">{{ $sppd->budget?->department?->name ?? '-' }}</dd>
				</div>
			</dl>

			@if ($narratives)
				<dl class="divide-y divide-slate-200 border-t border-slate-200">
					@foreach ($narratives as $title => $text)
						<div class="px-5 py-2.5">
							<dt class="{{ $label }}">{{ $title }}</dt>
							<dd class="mt-1 max-w-[75ch] whitespace-pre-line text-sm font-medium leading-relaxed text-slate-800">{{ $text }}</dd>
						</div>
					@endforeach
				</dl>
			@endif

			@if ($sppd->notes)
				<div class="border-t border-slate-200 px-5 py-2.5">
					<p class="{{ $label }}">Catatan tambahan</p>
					<p class="mt-1 max-w-[75ch] rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm leading-relaxed text-amber-900">{{ $sppd->notes }}</p>
				</div>
			@endif
		</section>

		{{-- Pembebanan Anggaran (tanpa pagu/realisasi) --}}
		@if ($sppd->budget)
			<section class="{{ $card }}">
				<h2 class="{{ $cardTitle }}">
					<i class="fa-solid fa-money-check-dollar text-xs text-primary-100"></i> Pembebanan Anggaran
				</h2>
				<dl class="{{ $fieldGrid }}">
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Kode rekening</dt>
						<dd class="{{ $value }} font-mono">{{ $sppd->budget->account_code ?? '-' }}</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Program</dt>
						<dd class="{{ $value }}">{{ $sppd->budget->program ?? '-' }}</dd>
					</div>
					<div class="{{ $fieldCell }} @lg:col-span-2">
						<dt class="{{ $label }}">Kegiatan</dt>
						<dd class="{{ $value }}">{{ $sppd->budget->activity ?? '-' }}</dd>
					</div>
				</dl>
			</section>
		@endif

		{{-- Lokasi Tujuan --}}
		@if ($sppd->destinations->count())
			<section class="{{ $card }}">
				<h2 class="{{ $cardTitle }}">
					<i class="fa-solid fa-map-location-dot text-xs text-primary-100"></i> Lokasi Tujuan
				</h2>
				<ul class="divide-y divide-slate-200">
					@foreach ($sppd->destinations as $dest)
						<li class="flex items-start gap-3 px-5 py-3">
							<i class="fa-solid fa-location-dot mt-0.5 text-sm text-slate-400"></i>
							<div class="min-w-0">
								<p class="text-sm font-semibold text-slate-800">{{ $dest->location_label }}</p>
								@if ($dest->address)
									<p class="mt-0.5 text-xs text-slate-500">{{ $dest->address }}</p>
								@endif
							</div>
						</li>
					@endforeach
				</ul>
			</section>
		@endif

		{{-- Pengikut --}}
		@if ($sppd->followers->count())
			<section class="{{ $card }}">
				<h2 class="{{ $cardTitle }}">
					<i class="fa-solid fa-users text-xs text-primary-100"></i> Daftar Pengikut
					<span class="font-medium text-primary-100">({{ $sppd->followers->count() }})</span>
				</h2>
				<ul class="flex flex-wrap gap-2 px-5 py-4">
					@foreach ($sppd->followers as $f)
						<li class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white px-2.5 py-1.5">
							<span class="flex size-6 shrink-0 items-center justify-center rounded bg-slate-100 text-[11px] font-bold text-slate-600">
								{{ strtoupper(substr($f->user->name, 0, 1)) }}
							</span>
							<span class="leading-tight">
								<span class="block text-sm font-semibold text-slate-800">{{ $f->user->name }}</span>
								@if ($f->travel_position)
									<span class="block text-xs text-slate-500">{{ $f->travel_position }}</span>
								@endif
							</span>
						</li>
					@endforeach
				</ul>
			</section>
		@endif
	</main>

	{{-- Footer institusional, senada footer aplikasi. --}}
	<footer class="border-t border-slate-200 bg-white/80">
		<div class="mx-auto flex max-w-4xl items-center justify-center gap-2 px-4 py-4 text-center text-xs text-slate-500">
			<img src="{{ asset('img/logo-sppd.png') }}" alt="" class="size-4 opacity-80">
			<span>Halaman ini bersifat informatif (hanya baca) &middot; &copy; {{ date('Y') }} Pemerintah Kota Kendari</span>
		</div>
	</footer>
</body>

</html>
