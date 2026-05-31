<!doctype html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<meta name="google" content="notranslate">
	<meta name="robots" content="index, follow">
	<title>
		@hasSection('title')
			@yield('title') -
		@endif{{ config('app.name') }}
	</title>
	<link rel="icon" href="{{ asset('img/logo-sppd.png') }}">
	@vite(['resources/css/app.css'])
</head>

<body class="font-sans text-slate-900" style="font-family: 'Poppins', sans-serif;">
	<div class="h-dvh lg:h-screen flex justify-center items-center px-4 py-5 sm:px-6 lg:px-8">

		<!-- Efek cahaya di dekat bulatan -->
		<div
			class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(186,230,253,0.28),transparent_18%),radial-gradient(circle_at_bottom_right,rgba(125,211,252,0.22),transparent_20%),linear-gradient(135deg,rgba(12,74,110,0.96),rgba(8,47,73,0.98),rgba(3,105,161,0.94))]">
		</div>
		<!-- Bulatan di pojok kiri atas -->
		<div
			class="pointer-events-none absolute left-4 top-8 h-24 w-24 animate-[float_9s_ease-in-out_infinite] rounded-full border border-sky-200/35 bg-sky-200/30 shadow-[0_16px_35px_rgba(14,165,233,0.22)] sm:left-10 sm:h-32 sm:w-32">
		</div>
		<!-- Bulatan di pojok kanan bawah -->
		<div
			class="pointer-events-none absolute bottom-8 right-5 h-20 w-20 animate-[sway_11s_ease-in-out_infinite] rounded-full border border-sky-200/35 bg-sky-200/30 shadow-[0_16px_35px_rgba(14,165,233,0.22)] sm:right-10 sm:h-28 sm:w-28">
		</div>

		<!-- Card login -->
		<div
			class="max-w-6xl grid w-full overflow-hidden rounded-[30px] border border-sky-100/25 bg-sky-950/30 shadow-[0_30px_100px_rgba(8,47,73,0.45)] backdrop-blur-[28px] lg:grid-cols-[0.95fr_1.05fr]">
			<div class="hidden flex-col justify-center gap-5 px-5 py-6 sm:px-7 sm:py-8 lg:flex lg:px-9 lg:py-9">
				<div
					class="inline-flex w-fit items-center gap-2 rounded-full border border-sky-100/35 bg-sky-100/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-sky-50">
					<span class="inline-block h-2 w-2 rounded-full bg-sky-200"></span>
					SPPD Kota Kendari
				</div>

				<div
					class="animate-[fadeUp_0.9s_ease] overflow-hidden rounded-3xl border border-sky-100/25 bg-sky-100/10 p-2 shadow-[0_18px_40px_rgba(14,165,233,0.16)]">
					<img src="{{ asset('img/walikota-dan-wakil.webp') }}" alt="Foto Kepala Daerah dan Wakil"
						class="h-full w-[70%] mx-auto rounded-2xl object-cover opacity-95">
				</div>

				<div class="flex flex-row items-center gap-3 px-1">
					<img src="{{ asset('img/logo-kendari.png') }}" alt="Logo Pemkot Kendari"
						class="h-20 rounded-2xl border border-sky-100/30 bg-sky-100/15 px-3 py-2 shadow-[0_10px_24px_rgba(14,165,233,0.14)] backdrop-blur-md sm:h-14">
					<img src="{{ asset('img/logo-bsre.png') }}" alt="Logo BSrE"
						class="h-20 rounded-2xl border border-sky-100/30 bg-sky-100/15 px-3 py-2 shadow-[0_10px_24px_rgba(14,165,233,0.14)] backdrop-blur-md sm:h-12">
				</div>

				<div class="space-y-1 px-1">
					<h1 class="text-[1.4rem] font-black leading-tight text-white sm:text-[1.5rem]">SPPD Elektronik<br>Kota
						Kendari</h1>
					<p class="text-sm leading-6 text-sky-50/90">Sistem perjalanan dinas modern untuk OPD.</p>
				</div>
			</div>

			<div
				class="flex flex-col justify-center border-t border-sky-100/25 bg-sky-100/10 px-5 py-6 text-slate-900 sm:px-7 sm:py-8 lg:border-t-0 lg:border-l lg:px-9">
				<div class="space-y-3">
					<div class="relative">
						<p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-200">Akses resmi</p>
						<div class="lg:hidden absolute -top-2 right-0 flex flex-row items-center gap-1.5">
							<img src="{{ asset('img/logo-kendari.png') }}" alt="Logo Pemkot Kendari"
								class="h-5">
							<img src="{{ asset('img/logo-bsre.png') }}" alt="Logo BSrE"
								class="h-5">
						</div>
					</div>
					<h2 class="text-[1.55rem] font-black text-white sm:text-[1.65rem]">Masuk ke Akun Anda</h2>
					<p class="text-sm leading-6 text-sky-50/90">Gunakan akun resmi untuk mengelola pengajuan, persetujuan, dan
						pelaporan perjalanan dinas.</p>
				</div>

				<div
					class="mt-6 rounded-3xl border border-sky-100/25 bg-sky-100/10 p-4 shadow-[0_20px_40px_rgba(14,165,233,0.12)] backdrop-blur-xl sm:p-5">
					@yield('content')
				</div>

				<div class="mt-6 border-t border-sky-100/25 pt-4 text-xs text-sky-50/90">
					<p>
						<span class="inline lg:hidden font-bold text-sky-100">SPPD Elektronik</span>
						<span class="font-bold text-sky-100">V {{ config('app.sppd_version') }}</span> • &copy; <?= date('Y') ?>
						Pemerintah Kota Kendari
					</p>
				</div>
			</div>
		</div>
	</div>

	<style>
		@keyframes float {

			0%,
			100% {
				transform: translateY(0px);
			}

			50% {
				transform: translateY(-10px);
			}
		}

		@keyframes sway {

			0%,
			100% {
				transform: translateY(0px) rotate(0deg);
			}

			50% {
				transform: translateY(-8px) rotate(5deg);
			}
		}

		@keyframes pulseGlow {

			0%,
			100% {
				opacity: 0.35;
				transform: scale(1);
			}

			50% {
				opacity: 0.7;
				transform: scale(1.1);
			}
		}

		@keyframes fadeUp {
			from {
				opacity: 0;
				transform: translateY(18px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
	</style>
</body>

</html>
