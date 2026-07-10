@props(['show', 'title', 'description' => null, 'icon' => null, 'maxWidth' => 'max-w-md', 'closeable' => true])

@php
	// Klik di luar card: tutup bila closeable; bila tidak, goyangkan card sebagai
	// isyarat bahwa modal harus ditutup lewat tombol.
	$outsideClick = $closeable ? ($show . ' = false') : 'shake = false; $nextTick(() => shake = true)';
@endphp
<div x-data="{ shake: false }" x-show="{{ $show }}"
	{{-- Kunci scroll body selama modal terbuka. Pakai counter global agar aman
	     bila ada beberapa modal: body baru bisa discroll lagi setelah semua tutup. --}}
	x-init="
		const lock = (open) => {
			window.__modalOpenCount = Math.max(0, (window.__modalOpenCount || 0) + (open ? 1 : -1));
			document.body.style.overflow = window.__modalOpenCount > 0 ? 'hidden' : '';
		};
		if ({{ $show }}) lock(true);
		$watch('{{ $show }}', (open) => lock(open));
	"
	class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
	<!-- Backdrop -->
	<div x-show="{{ $show }}"
		x-transition:enter="transition ease-out duration-200"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-150"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
		@click="{{ $outsideClick }}">
	</div>

	<!-- Modal Container (to center the modal) -->
	<div class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none"
		@click.self="{{ $outsideClick }}">

		<div x-show="{{ $show }}"
			x-transition:enter="transition ease-out duration-300"
			x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
			x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
			x-transition:leave="transition ease-in duration-200"
			x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
			x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
			:class="shake && 'animate-modal-shake'"
			@animationend="shake = false"
			class="w-full {{ $maxWidth }} rounded border border-slate-200 bg-white shadow-2xl overflow-hidden pointer-events-auto"
			@keydown.escape.window="if ({{ $show }}) { {{ $show }} = false }">

			{{-- Header --}}
			<div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
				<div class="flex items-center gap-2">
					@if ($icon)
						@if (str_starts_with($icon, 'fa-'))
							<i class="{{ $icon }} text-base"></i>
						@else
							{!! $icon !!}
						@endif
					@endif
					<div>
						<h3 class="text-sm font-bold text-slate-800">{{ $title }}</h3>
						@if ($description)
							<p class="text-[11px] text-slate-500">{{ $description }}</p>
						@endif
					</div>
				</div>
				<button type="button" @click="{{ $closeable ? $show . ' = false' : '' }}"
					@if(!$closeable) style="display:none" @endif
					class="rounded-full border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>

			{{-- Content --}}
			<div class="p-4">
				{{ $slot }}
			</div>

			{{-- Footer --}}
			@if (isset($footer))
				<div {{ $footer->attributes->merge(['class' => 'border-t border-slate-100 px-4 py-3']) }}>
					{{ $footer }}
				</div>
			@endif
		</div>
	</div>
</div>
