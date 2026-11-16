{{-- Penanda layar mode development (APP_ENV local/development): label kecil di tengah atas.
     Tidak menangkap klik dan tidak ikut dicetak; di produksi tidak dirender sama sekali. --}}
@if (app()->environment('local', 'development'))
	<div role="status"
		class="pointer-events-none fixed left-1/2 top-0 z-[9999] -translate-x-1/2 rounded-b bg-amber-400 px-3 py-0.5 text-[10px] font-bold uppercase tracking-[0.2em] text-amber-950 shadow-sm print:hidden">
		Mode Development · {{ app()->environment() }}
	</div>
@endif
