@props(['compact' => false])

{{-- Ajakan mengisi survei kepuasan (dipakai di panel kiri desktop & panel kanan mobile).
     Survei ditampilkan lewat <dialog> + iframe karena widget SPBE membatasi host embed;
     src iframe baru diisi saat pertama dibuka agar tidak membebani load halaman login. --}}
<div {{ $attributes->merge(['class' => 'text-center']) }} x-data="{ loading: true }">
	<button type="button"
		@click="$refs.frame.src || ($refs.frame.src = $refs.frame.dataset.src); $refs.dialog.showModal()"
		class="inline-flex items-center gap-2 rounded border border-sky-100/30 bg-sky-100/15 font-bold uppercase tracking-[0.15em] text-white shadow-[0_10px_24px_rgba(14,165,233,0.14)] backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-sky-100/25 {{ $compact ? 'px-3.5 py-2 text-[11px]' : 'px-5 py-2.5 text-sm' }}">
		<i class="fa-solid fa-clipboard-check text-sky-200"></i>
		Isi Survei Kepuasan
		<i class="fa-solid fa-chevron-right text-[10px]"></i>
	</button>

	{{-- Native <dialog>: tampil di top layer, aman dari ancestor ber-transform (animasi dash-enter).
	     Panel kaca gelap satu bahasa dengan kartu login; klik backdrop atau Esc menutup. --}}
	<dialog x-ref="dialog" @click.self="$refs.dialog.close()"
		class="survey-dialog m-auto w-[min(96vw,58rem)] overflow-hidden rounded border border-sky-100/25 bg-sky-800/80 p-0 text-left shadow-[0_40px_120px_rgba(8,47,73,0.65)] backdrop-blur-2xl backdrop:bg-black/50 backdrop:backdrop-blur-sm">
		<div class="flex h-[min(88dvh,46rem)] flex-col">
			<header class="relative flex items-center gap-3.5 overflow-hidden border-b border-sky-100/20 px-4 py-3.5 sm:px-6">
				{{-- Kilau lembut di sudut header, senada bulatan dekoratif laman login --}}
				<span class="pointer-events-none absolute -left-12 -top-14 h-36 w-36 rounded-full bg-sky-300/25 blur-3xl"></span>
				<span class="pointer-events-none absolute -right-16 -top-20 h-40 w-40 rounded-full bg-sky-400/15 blur-3xl"></span>

				<span
					class="relative flex size-10 shrink-0 items-center justify-center rounded border border-sky-100/30 bg-sky-100/15 text-sky-200 shadow-[0_10px_24px_rgba(14,165,233,0.25)]">
					<i class="fa-solid fa-clipboard-check text-base"></i>
				</span>
				<div class="relative min-w-0 flex-1">
					<p class="text-[10px] font-bold uppercase tracking-[0.2em] text-sky-200">Survei Kepuasan Masyarakat</p>
					<h2 class="truncate text-base font-black text-white sm:text-lg">Penilaian Layanan SPPD</h2>
				</div>
				<button type="button" @click="$refs.dialog.close()" aria-label="Tutup survei"
					class="relative flex size-9 shrink-0 items-center justify-center rounded border border-sky-100/25 bg-sky-100/10 text-sky-100/80 transition hover:bg-sky-100/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-200">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</header>

			<div class="relative flex-1 bg-white">
				{{-- Overlay pemuatan: tampil sampai iframe survei selesai dimuat --}}
				<div x-show="loading" x-transition.opacity.duration.300ms
					class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-[linear-gradient(160deg,#f8fafc,#edf6fc)]">
					<span class="flex size-12 items-center justify-center rounded-full border border-sky-200 bg-white text-sky-500 shadow-[0_10px_30px_rgba(14,165,233,0.2)]">
						<i class="fa-solid fa-spinner fa-spin text-lg"></i>
					</span>
					<p class="text-sm font-semibold text-slate-600">Memuat survei…</p>
				</div>
				<iframe x-ref="frame" data-src="{{ config('app.survey_url') }}" title="Survei Kepuasan Layanan"
					@load="if ($refs.frame.src) loading = false" class="h-full w-full border-0"></iframe>
			</div>

			<footer class="border-t border-sky-100/20 px-4 py-2 text-center sm:px-6">
				<p class="text-[11px] text-sky-50/70">
					Terhubung dengan <span class="font-bold text-sky-100">Survei Digital SPBE</span><span class="hidden sm:inline"> · tekan
						<kbd class="rounded border border-sky-100/30 bg-sky-100/10 px-1.5 py-0.5 font-sans text-[10px] font-bold text-sky-100">Esc</kbd>
						untuk menutup</span>
				</p>
			</footer>
		</div>
	</dialog>

	@once
		<style>
			/* Animasi masuk dialog survei: naik halus + memudar, backdrop ikut memudar */
			dialog.survey-dialog[open] {
				animation: surveyDialogIn 0.35s cubic-bezier(0.22, 1, 0.36, 1);
			}

			dialog.survey-dialog[open]::backdrop {
				animation: surveyBackdropIn 0.35s ease;
			}

			@keyframes surveyDialogIn {
				from {
					opacity: 0;
					transform: translateY(16px) scale(0.97);
				}

				to {
					opacity: 1;
					transform: translateY(0) scale(1);
				}
			}

			@keyframes surveyBackdropIn {
				from {
					opacity: 0;
				}

				to {
					opacity: 1;
				}
			}

			@media (prefers-reduced-motion: reduce) {

				dialog.survey-dialog[open],
				dialog.survey-dialog[open]::backdrop {
					animation: none;
				}
			}
		</style>
	@endonce
	<p class="mt-2 text-sky-50/80 {{ $compact ? 'text-[11px]' : 'text-xs' }}"><i
			class="fa-solid fa-circle-info mr-1"></i>Bantu kami meningkatkan kualitas layanan dengan mengisi survei singkat.
	</p>
</div>
