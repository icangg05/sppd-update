@props(['compact' => false])

{{-- Ajakan mengisi survei kepuasan (dipakai di panel kiri desktop & panel kanan mobile) --}}
<div {{ $attributes->merge(['class' => 'text-center']) }}>
	<a href="{{ config('app.survey_url') }}" target="_blank" rel="noopener"
		class="inline-flex items-center gap-2 rounded border border-sky-100/30 bg-sky-100/15 font-bold uppercase tracking-[0.15em] text-white shadow-[0_10px_24px_rgba(14,165,233,0.14)] backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-sky-100/25 {{ $compact ? 'px-3.5 py-2 text-[11px]' : 'px-5 py-2.5 text-sm' }}">
		<i class="fa-solid fa-clipboard-check text-sky-200"></i>
		Isi Survei Kepuasan
		<i class="fa-solid fa-chevron-right text-[10px]"></i>
	</a>
	<p class="mt-2 text-sky-50/80 {{ $compact ? 'text-[11px]' : 'text-xs' }}"><i
			class="fa-solid fa-circle-info mr-1"></i>Bantu kami meningkatkan kualitas layanan dengan mengisi survei singkat.
	</p>
</div>
