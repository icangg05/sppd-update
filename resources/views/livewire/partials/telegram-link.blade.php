{{-- Komponen hubungkan Telegram bersama (UserForm & Profile).
     Memakai state dari trait InteractsWithTelegramLink. Pemanggil harus
     menyediakan Alpine `showUnlinkModal` pada elemen pembungkus. --}}

{{-- Polling status tautan --}}
@if ($isPolling)
	<div wire:poll.1s.keep-alive="checkLinkStatus"></div>
@endif

{{-- Modal Hubungkan Telegram --}}
<x-ui.modal show="$wire.showLinkModal" :closeable="false" title="Hubungkan Telegram"
	description="Tekan Start di bot untuk konfirmasi" icon="fa-brands fa-telegram text-sky-500">
	<div class="space-y-4">
		@if (!$isVerified && !$isFailed && !$isTimedOut)
			{{-- Langkah singkat --}}
			<ol class="space-y-1.5 text-xs text-slate-600">
				<li class="flex gap-2">
					<span class="flex size-4 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">1</span>
					<span>Tekan <strong>Buka Telegram</strong>, atau pindai QR di bawah dengan kamera ponsel.</span>
				</li>
				<li class="flex gap-2">
					<span class="flex size-4 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">2</span>
					<span>Di chat bot, tekan tombol <strong>Start</strong> / <strong>Mulai</strong>.</span>
				</li>
				<li class="flex gap-2">
					<span class="flex size-4 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">3</span>
					<span>Status di bawah berubah otomatis setelah bot menerima pesan Anda.</span>
				</li>
			</ol>

			{{-- QR + salin tautan: memudahkan admin mengirim tautan ke pegawai --}}
			<div class="flex items-center gap-4 rounded border border-slate-200 bg-slate-50 p-3"
				x-data="{ copied: false }">
				<div class="shrink-0 rounded bg-white p-1.5 ring-1 ring-slate-200 [&_svg]:size-28">
					{!! $this->linkQrSvg !!}
				</div>
				<div class="min-w-0 space-y-2">
					<p class="text-[11px] leading-relaxed text-slate-500">
						Tautan berlaku 15 menit dan hanya bisa dipakai sekali. Bila menghubungkan untuk pegawai lain,
						kirim tautan ini ke pegawai tersebut.
					</p>
					<p class="break-all font-mono text-[10px] text-slate-600 select-all">{{ $deeplinkUrl }}</p>
					{{-- wire:target membatasi state loading ke pembuatan tautan baru, agar tombol
					     tidak berkedip setiap polling. --}}
					<x-ui.button variant="secondary" size="sm" wire:target="openLinkModal,retryLink"
						@click="navigator.clipboard && navigator.clipboard.writeText($wire.deeplinkUrl).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
						<x-slot:icon>
							<i class="fa-regular fa-copy text-xs" x-show="!copied"></i>
							<i class="fa-solid fa-check text-xs text-emerald-600" x-show="copied" style="display: none"></i>
						</x-slot:icon>
						<span x-text="copied ? 'Tersalin' : 'Salin Tautan'">Salin Tautan</span>
					</x-ui.button>
				</div>
			</div>
		@endif

		{{-- Status Polling --}}
		<div
			class="rounded p-3 text-center text-xs font-medium border
			{{ $isVerified ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : '' }}
			{{ $isFailed ? 'bg-red-50 text-red-800 border-red-200' : '' }}
			{{ !$isVerified && !$isFailed && !$isTimedOut ? 'bg-amber-50 text-amber-800 border-amber-200' : '' }}
			{{ $isTimedOut && !$isFailed && !$isVerified ? 'bg-slate-50 text-slate-600 border-slate-200' : '' }}">

			{{-- Pending --}}
			@if (!$isVerified && !$isFailed && !$isTimedOut)
				<span class="flex items-center justify-center gap-2">
					<i class="fa-solid fa-circle-notch fa-spin text-amber-600"></i>
					Menunggu Anda menekan Start di bot Telegram...
				</span>
			@endif

			{{-- Verified --}}
			@if ($isVerified)
				<span class="flex items-center justify-center gap-2">
					<i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
					Telegram berhasil terhubung{{ $telegramUsername ? ' sebagai @' . $telegramUsername : '' }}.
				</span>
			@endif

			{{-- Failed --}}
			@if ($isFailed)
				<div class="space-y-2">
					<span class="flex items-center justify-center gap-2">
						<i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
						Gagal Menghubungkan
					</span>
					<p class="text-[11px] text-red-600 leading-relaxed">{{ $failedMessage }}</p>
				</div>
			@endif

			{{-- Timed out --}}
			@if ($isTimedOut && !$isFailed && !$isVerified)
				<div class="space-y-2">
					<span class="flex items-center justify-center gap-2">
						<i class="fa-solid fa-clock text-slate-500 text-base"></i>
						Waktu menunggu habis (5 menit)
					</span>
					<p class="text-[11px] text-slate-500">Silakan coba lagi dengan menekan tombol di bawah.</p>
				</div>
			@endif
		</div>
	</div>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<x-ui.button variant="secondary" wire:click="closeLinkModal" wire:target="closeLinkModal" class="flex-1">Tutup</x-ui.button>

		{{-- Buka Telegram (pending). <a> biasa, bukan <x-ui.button href>, karena tautan eksternal
		     harus dibuka di tab baru tanpa wire:navigate. --}}
		@if (!$isVerified && !$isFailed && !$isTimedOut)
			<a href="{{ $deeplinkUrl }}" target="_blank" rel="noopener"
				class="flex-1 inline-flex items-center justify-center gap-2 rounded bg-sky-500 px-4 py-2 text-sm font-medium text-white shadow-2xs transition-colors hover:bg-sky-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-1 whitespace-nowrap">
				<i class="fa-brands fa-telegram shrink-0"></i>
				<span>Buka Telegram</span>
			</a>
		@endif

		{{-- Terhubung --}}
		@if ($isVerified)
			<x-ui.button variant="success" disabled class="flex-1">
				<x-slot:icon><i class="fa-solid fa-circle-check"></i></x-slot:icon>
				Terhubung
			</x-ui.button>
		@endif

		{{-- Coba Lagi (failed / timed out) --}}
		@if ($isFailed || $isTimedOut)
			<x-ui.button variant="warning" wire:click="retryLink" wire:target="retryLink" class="flex-1">
				<x-slot:icon><i class="fa-solid fa-rotate-right"></i></x-slot:icon>
				Coba Lagi
			</x-ui.button>
		@endif
	</x-slot>
</x-ui.modal>

{{-- Modal Konfirmasi Kirim Pesan Tes --}}
<x-ui.modal show="$wire.showTestConfirm" :closeable="false" title="Kirim Pesan Tes?"
	description="Konfirmasi sebelum pesan dikirim" icon="fa-solid fa-paper-plane text-primary-600">
	<p class="text-sm text-slate-600">
		Pesan tes notifikasi akan dikirim ke akun Telegram
		<strong>{{ $telegramUsername ? '@' . $telegramUsername : 'yang terhubung' }}</strong>. Lanjutkan?
	</p>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<x-ui.button variant="secondary" wire:click="closeTestConfirm" wire:target="sendTestMessage" class="flex-1">
			Batal
		</x-ui.button>
		<x-ui.button wire:click="sendTestMessage" wire:target="sendTestMessage" class="flex-1">
			<x-slot:icon><i class="fa-solid fa-paper-plane"></i></x-slot:icon>
			Ya, Kirim
		</x-ui.button>
	</x-slot>
</x-ui.modal>

{{-- Modal Konfirmasi Putuskan Telegram --}}
<x-ui.modal show="showUnlinkModal" :closeable="false" title="Putuskan Telegram?"
	description="Notifikasi berhenti sampai dihubungkan lagi" icon="fa-solid fa-triangle-exclamation text-amber-600">
	<p class="text-sm text-slate-600">
		Akun Telegram <strong>{{ $telegramUsername ? '@' . $telegramUsername : 'yang terhubung' }}</strong> akan diputus
		dari akun ini.
		<br><br>
		Notifikasi pengajuan &amp; persetujuan SPPD <strong>tidak akan dikirim</strong> sampai Anda menghubungkan
		Telegram kembali.
	</p>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<x-ui.button variant="secondary" @click="showUnlinkModal = false" wire:target="unlinkTelegram" class="flex-1">Batal</x-ui.button>
		<x-ui.button variant="warning" wire:click="unlinkTelegram" wire:target="unlinkTelegram" @click="showUnlinkModal = false" class="flex-1">
			Ya, Putuskan
		</x-ui.button>
	</x-slot>
</x-ui.modal>
