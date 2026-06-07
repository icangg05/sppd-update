<div class="contents">
	@if ($count > 0)
		<span class="inline-flex items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white min-w-[18px]">
			{{ $count > 99 ? '99+' : $count }}
		</span>
	@endif
</div>
