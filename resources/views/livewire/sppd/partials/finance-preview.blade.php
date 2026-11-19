{{-- Pratinjau blok tanda tangan satu dokumen biaya (SppdFinanceOfficials::previewDocuments). Kertasnya dirender
     selebar area isi PDF dengan font & ukuran yang sama (Times 9pt; kertas letter 612pt dikurangi margin tiap
     template), lalu diperkecil agar muat, jadi baris patahnya sama dengan PDF (mis. label KPA tetap satu baris).
     Slot peran yang sedang dipilih disorot; label KPA mengikuti isian modal seketika ($wire.financeLabel). --}}
@php
	// Lebar isi (px, 96 dpi) = 612pt − margin kiri+kanan @page; judul & jarak tanda tangan mengikuti template.
	$layout = match ($doc['key']) {
		'kuitansi' => ['width' => 703, 'title' => '14pt', 'gap' => 55, 'line' => 1.3],
		'riil' => ['width' => 642, 'title' => '14pt', 'gap' => 50, 'line' => 1.3],
		'rincian' => ['width' => 680, 'title' => '10pt', 'gap' => 35, 'line' => 1.7],
	};
	$pad = 28;
	$pageWidth = $layout['width'] + 2 * $pad;
	$center = $doc['align'] === 'center';
@endphp
<div class="rounded bg-slate-100 p-3"
	x-data="{ zoom: 1, fit() { const w = $el.clientWidth - 24; if (w > 0) this.zoom = Math.max(0.7, Math.min(1, w / {{ $pageWidth }})); } }"
	x-init="fit(); new ResizeObserver(() => fit()).observe($el)">
	{{-- Di layar sempit kertas tidak diperkecil di bawah 70%; sisanya digeser ke samping. --}}
	<div class="overflow-x-auto">
		{{-- :style objek (bukan string) agar digabung dengan style statis di bawah, tidak menimpanya. --}}
		<div class="mx-auto bg-white text-black shadow-md ring-1 ring-slate-200" :style="{ zoom: zoom }"
			style="width: {{ $pageWidth }}px; padding: {{ $pad }}px; font-family: 'Times New Roman', Times, 'Liberation Serif', serif; font-size: 9pt; line-height: {{ $layout['line'] }};">
			<p class="text-center font-bold" style="font-size: {{ $layout['title'] }};">{{ $doc['heading'] }}</p>
			<div class="mt-3 space-y-2" aria-hidden="true">
				@foreach (['100%', '92%', '64%'] as $width)
					<div class="h-2 rounded-full bg-slate-200" style="width: {{ $width }};"></div>
				@endforeach
			</div>
			@unless ($doc['key'] === 'rincian')
				<p class="mt-4 text-right {{ $doc['key'] === 'kuitansi' ? 'uppercase' : '' }}">Kendari, …</p>
			@endunless

			@foreach ($doc['sections'] as $section)
				@if ($section['divider'])
					<div class="mt-4 border-t border-black"></div>
					<p class="mt-4 text-center">{{ $section['divider'] }}</p>
					<div class="mt-3 space-y-2" aria-hidden="true">
						@foreach (['52%', '44%'] as $width)
							<div class="h-2 rounded-full bg-slate-200" style="width: {{ $width }};"></div>
						@endforeach
					</div>
				@endif

				@php
					// Baris keterangan disamakan tingginya (seperti <br /> di PDF), jadi judul kolom sejajar.
					$captionLines = collect($section['slots'])->max(fn ($slot) => count($slot['caption']));
					// Lebar kolom tabel tanda tangan: rincian 55% · 45%, lainnya sama rata.
					$columns = $doc['key'] === 'rincian' && count($section['slots']) === 2
						? '55fr 45fr'
						: implode(' ', array_fill(0, count($section['slots']), '1fr'));
				@endphp
				<div class="mt-3 grid" style="grid-template-columns: {{ $columns }};">
					@foreach ($section['slots'] as $slot)
						<div @class([
							'relative min-w-0 rounded px-1 py-0.5',
							'text-center' => $center,
							'bg-primary-50 ring-2 ring-primary-400' => $slot['highlight'],
						])>
							@if ($slot['highlight'])
								<span class="absolute -top-2.5 right-1 rounded bg-primary-600 px-1.5 py-px font-sans text-[10px] font-bold uppercase tracking-wide text-white">Dipilih</span>
							@endif
							@foreach (array_pad($slot['caption'], -$captionLines, null) as $line)
								<p>{!! $line === null ? '&nbsp;' : e($line) !!}</p>
							@endforeach
							<p
								@if ($slot['live'])
									x-text="(String($wire.financeLabel ?? '').trim() || @js(\App\Services\SppdFinanceOfficials::DEFAULT_KPA_LABEL)){{ $slot['upper'] ? '.toLocaleUpperCase()' : '' }}"
								@endif>{{ $slot['title'] }}</p>
							<div style="height: {{ $layout['gap'] }}px;" aria-hidden="true"></div>
							@if ($slot['name'])
								<p class="font-bold underline">{{ $slot['name'] }}</p>
								@if ($slot['nip'])
									<p>NIP. {{ $slot['nip'] }}</p>
								@endif
							@else
								<p class="font-sans text-[11px] italic text-slate-500">belum dipilih</p>
							@endif
						</div>
					@endforeach
				</div>
			@endforeach
		</div>
	</div>
	<p class="mt-2 text-center text-[11px] text-slate-600">Pratinjau blok tanda tangan sebelum disimpan, dengan ukuran huruf dan lebar kolom seperti PDF; isi dokumen lainnya disederhanakan.</p>
</div>
