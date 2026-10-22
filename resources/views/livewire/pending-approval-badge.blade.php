<div class="contents">
	@if ($count > 0)
		{{-- Bulat penuh untuk satu digit; melebar jadi kapsul untuk 10+ / 99+. --}}
		<span class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white">
			{{ $count > 99 ? '99+' : $count }}
		</span>
	@endif
</div>
