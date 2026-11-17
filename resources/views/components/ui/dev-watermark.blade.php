{{-- Penanda layar di tengah atas: mode development (APP_ENV local/development) dan simulasi
     TTE (TTE_SIMULATION). Tidak menangkap klik dan tidak ikut dicetak; bila keduanya
     nonaktif tidak dirender sama sekali. --}}
@php
	$isDev = app()->environment('local', 'development');
	$tteTesting = \App\Services\Tte\SimulatedSignService::enabled();
@endphp
@if ($isDev || $tteTesting)
	<div role="status"
		class="pointer-events-none fixed left-1/2 top-0 z-[9999] flex -translate-x-1/2 overflow-hidden whitespace-nowrap rounded-b text-[10px] font-bold uppercase tracking-[0.2em] shadow-sm print:hidden">
		@if ($isDev)
			<span class="bg-amber-400 px-3 py-0.5 text-amber-950">Mode Development · {{ app()->environment() }}</span>
		@endif
		@if ($tteTesting)
			<span class="bg-rose-600 px-3 py-0.5 text-white" title="Tanda tangan elektronik disimulasikan, tidak dikirim ke BSrE">TTE Testing</span>
		@endif
	</div>
@endif
