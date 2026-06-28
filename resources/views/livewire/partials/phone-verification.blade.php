{{-- Komponen verifikasi WhatsApp bersama (UserForm & Profile).
     Memakai state dari trait InteractsWithPhoneVerification. Pemanggil harus
     menyediakan Alpine `showResetModal` pada elemen pembungkus. --}}

{{-- Polling status verifikasi --}}
@if ($isPolling)
	<div wire:poll.1s.keep-alive="checkVerification"></div>
@endif

{{-- Modal Verifikasi WhatsApp --}}
<x-ui.modal show="$wire.showVerifyModal" :closeable="false" title="Verifikasi Nomor WhatsApp"
	description="Kirim pesan ke operator untuk konfirmasi" icon="fa-brands fa-whatsapp text-emerald-600">
	<div class="space-y-4">
		{{-- Instruksi singkat --}}
		<p class="text-xs text-slate-500">Kirim pesan verifikasi di bawah ini melalui WhatsApp. Status akan diperbarui
			otomatis setelah pesan diterima.</p>

		{{-- Template pesan --}}
		<div>
			<p class="text-xs font-semibold text-slate-600 mb-1.5">Pesan Verifikasi:</p>
			<div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap font-mono">{{ $verificationTemplate }}</div>
		</div>

		{{-- Status Polling: 3 states --}}
		<div
			class="rounded-lg p-3 text-center text-xs font-medium border
			{{ $isVerified ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : '' }}
			{{ $isFailed ? 'bg-red-50 text-red-800 border-red-200' : '' }}
			{{ !$isVerified && !$isFailed && !$isTimedOut ? 'bg-amber-50 text-amber-800 border-amber-200' : '' }}
			{{ $isTimedOut && !$isFailed && !$isVerified ? 'bg-slate-50 text-slate-600 border-slate-200' : '' }}">

			{{-- Pending --}}
			@if (!$isVerified && !$isFailed && !$isTimedOut)
				<span class="flex items-center justify-center gap-2">
					<i class="fa-solid fa-circle-notch fa-spin text-amber-600"></i>
					Menunggu pesan WhatsApp dikirim...
				</span>
			@endif

			{{-- Verified --}}
			@if ($isVerified)
				<span class="flex items-center justify-center gap-2">
					<i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
					Nomor WhatsApp Berhasil Diverifikasi.
				</span>
			@endif

			{{-- Failed --}}
			@if ($isFailed)
				<div class="space-y-2">
					<span class="flex items-center justify-center gap-2">
						<i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
						Verifikasi Gagal
					</span>
					<p class="text-[11px] text-red-600 leading-relaxed">{{ $failedMessage }}</p>
				</div>
			@endif

			{{-- Timed out --}}
			@if ($isTimedOut && !$isFailed && !$isVerified)
				<div class="space-y-2">
					<span class="flex items-center justify-center gap-2">
						<i class="fa-solid fa-clock text-slate-500 text-base"></i>
						Waktu verifikasi habis (5 menit)
					</span>
					<p class="text-[11px] text-slate-500">Silakan coba lagi dengan menekan tombol di bawah.</p>
				</div>
			@endif
		</div>
	</div>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<button type="button" wire:click="closeVerifyModal"
			class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
			Tutup
		</button>

		{{-- Kirim via WhatsApp (pending) --}}
		@if (!$isVerified && !$isFailed && !$isTimedOut)
			<a href="{{ $deeplinkUrl }}" target="_blank" rel="noopener"
				class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-green-700 whitespace-nowrap">
				<i class="fa-brands fa-whatsapp shrink-0"></i>
				<span>Kirim via WhatsApp</span>
			</a>
		@endif

		{{-- Terverifikasi --}}
		@if ($isVerified)
			<button type="button" disabled
				class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-xs font-bold text-white shadow cursor-not-allowed whitespace-nowrap">
				<i class="fa-solid fa-circle-check text-sm shrink-0"></i>
				<span>Terverifikasi</span>
			</button>
		@endif

		{{-- Coba Lagi (failed / timed out) --}}
		@if ($isFailed || $isTimedOut)
			<button type="button" wire:click="retryVerification"
				class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 py-2.5 text-xs font-bold text-white shadow transition hover:bg-amber-600 whitespace-nowrap">
				<i class="fa-solid fa-rotate-right text-sm shrink-0"></i>
				<span>Coba Lagi</span>
			</button>
		@endif
	</x-slot>
</x-ui.modal>

{{-- Modal Konfirmasi Kirim Pesan Tes --}}
<x-ui.modal show="$wire.showTestConfirm" :closeable="false" title="Kirim Pesan Tes?"
	description="Konfirmasi sebelum pesan dikirim" icon="fa-solid fa-paper-plane text-cyan-600">
	<p class="text-sm text-slate-600">
		Pesan tes notifikasi WhatsApp akan dikirim ke nomor <strong>{{ $phone }}</strong>. Lanjutkan?
	</p>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<button type="button" wire:click="closeTestConfirm" wire:loading.attr="disabled" wire:target="sendTestMessage"
			class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 disabled:opacity-50">
			Batal
		</button>
		<button type="button" wire:click="sendTestMessage" wire:loading.attr="disabled" wire:target="sendTestMessage"
			class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-cyan-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-cyan-700 disabled:opacity-50">
			<span wire:loading.remove wire:target="sendTestMessage" class="inline-flex items-center gap-2">
				<i class="fa-solid fa-paper-plane"></i> Ya, Kirim
			</span>
			<span wire:loading wire:target="sendTestMessage" class="inline-flex items-center gap-2">
				<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...
			</span>
		</button>
	</x-slot>
</x-ui.modal>

{{-- Modal Konfirmasi Ganti Nomor --}}
<x-ui.modal show="showResetModal" :closeable="false" title="Konfirmasi Ganti Nomor"
	description="Tindakan ini membutuhkan verifikasi ulang" icon="fa-solid fa-triangle-exclamation text-amber-600">
	<div class="space-y-4">
		<p class="text-sm text-slate-600">
			Apakah Anda yakin ingin mengganti nomor WhatsApp?
			<br><br>
			Status verifikasi pada nomor sebelumnya akan <strong>dihapus</strong> dan Anda harus melakukan proses verifikasi
			ulang untuk nomor yang baru.
		</p>
	</div>

	<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
		<button type="button" @click="showResetModal = false"
			class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
			Batal
		</button>
		<button type="button" wire:click="resetPhoneVerification" @click="showResetModal = false"
			class="flex-1 rounded-lg bg-amber-500 py-2.5 text-xs font-bold text-white shadow transition hover:bg-amber-600">
			Ya, Ganti Nomor
		</button>
	</x-slot>
</x-ui.modal>
