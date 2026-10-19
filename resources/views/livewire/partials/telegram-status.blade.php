{{-- Baris status akun Telegram (UserForm & Profile), memakai state trait
     InteractsWithTelegramLink. $testable = tampilkan tombol Tes Pesan. --}}
<div>
	<p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-600">Akun Telegram</p>

	@if ($telegramLinked)
		<div class="flex flex-wrap items-center gap-2 rounded border border-emerald-200 bg-emerald-50/60 px-3 py-2.5">
			<i class="fa-solid fa-circle-check text-emerald-600"></i>
			<span class="min-w-0 flex-1 truncate text-sm text-slate-700">
				Terhubung
				@if ($telegramUsername)
					sebagai <strong class="font-semibold">{{ '@' . $telegramUsername }}</strong>
				@endif
			</span>
			@if ($testable ?? true)
				<x-ui.button variant="secondary" size="sm" wire:click="confirmTestMessage" wire:target="confirmTestMessage"
					title="Tes kirim pesan ke akun Telegram ini">
					<x-slot:icon><i class="fa-solid fa-paper-plane text-xs text-primary-600"></i></x-slot:icon>
					Tes Pesan
				</x-ui.button>
			@endif
			<x-ui.button variant="secondary" size="sm" @click="showUnlinkModal = true" wire:target="unlinkTelegram"
				title="Putuskan akun Telegram">
				<x-slot:icon><i class="fa-solid fa-link-slash text-xs text-amber-600"></i></x-slot:icon>
				Putuskan
			</x-ui.button>
		</div>
		<p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-emerald-600">
			<i class="fa-solid fa-circle-info mt-0.5"></i>
			<span>Notifikasi SPPD dikirim ke chat bot Telegram ini.</span>
		</p>
	@else
		<div class="flex flex-wrap items-center gap-3 rounded border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5">
			<span class="min-w-0 flex-1 text-xs text-slate-500">
				Belum terhubung. Hubungkan agar menerima notifikasi SPPD.
			</span>
			<x-ui.button size="sm" wire:click="openLinkModal" wire:target="openLinkModal">
				<x-slot:icon><i class="fa-brands fa-telegram"></i></x-slot:icon>
				Hubungkan Telegram
			</x-ui.button>
		</div>
		<p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-slate-500">
			<i class="fa-solid fa-circle-info mt-0.5"></i>
			<span>Butuh aplikasi Telegram. Lihat <a href="{{ route('guide.telegram') }}" wire:navigate
					class="font-semibold text-primary-700 underline-offset-2 hover:underline">panduan</a>.</span>
		</p>
	@endif
</div>
