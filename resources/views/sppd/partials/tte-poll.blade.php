{{-- Selama ada TTE berstatus proses: cek status tiap 5 detik, muat ulang halaman begitu selesai
     agar status & tautan cetak (dokumen bertanda tangan) ikut terbarui. Berhenti bila pengguna
     sudah pindah halaman lewat wire:navigate. --}}
@push('scripts')
	<script>
		(function() {
			const path = location.pathname;
			const timer = setInterval(() => {
				if (location.pathname !== path) return clearInterval(timer);
				fetch(@js(route('sppd.sign.batch-status', $sppd)), { headers: { Accept: 'application/json' } })
					.then((response) => response.json())
					.then((data) => {
						if (!data.is_processing) {
							clearInterval(timer);
							window.location.reload();
						}
					})
					.catch(() => {});
			}, 5000);
		})();
	</script>
@endpush
