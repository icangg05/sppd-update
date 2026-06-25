@extends('layouts.guest')

@section('title', 'Login')

@section('content')
	@if ($errors->any())
		<div class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm shadow-sm" role="alert">
			<!-- Icon Gembok / Peringatan Keamanan -->
			<svg class="h-5 w-5 shrink-0 text-red-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
				stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
			</svg>

			<div>
				<h3 class="font-bold text-red-900 text-xs md:text-sm mb-1">Gagal Melakukan Autentikasi</h3>
				<ul class="list-none space-y-1 text-red-700 text-xs md:text-sm font-medium">
					@foreach ($errors->all() as $error)
						<li class="flex items-center gap-1.5">
							<span class="inline-block h-1 w-1 rounded-full bg-red-600"></span>
							{{ $error }}
						</li>
					@endforeach
				</ul>
			</div>
		</div>
	@endif

	<form action="{{ route('login') }}" method="post" class="mt-6 space-y-4">
		@csrf

		<div>
			<label for="username" class="block text-sm font-semibold text-sky-50">Username</label>
			<input

				type="text"
				name="username"
				id="username"
				value="{{ old('username', 'admin_diskominfo') }}"
				required
				autofocus
				class="mt-2 w-full rounded-2xl border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan username">
		</div>

		<div>
			<label for="password" class="block text-sm font-semibold text-sky-50">Password</label>
			<input
				value="pass1234"
				type="password"
				name="password"
				id="password"
				required
				class="mt-2 w-full rounded-2xl border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan password">
		</div>

		<button
			type="submit"
			class="w-full rounded-2xl bg-sky-300 px-4 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 shadow-[0_18px_40px_rgba(125,211,252,0.25)] transition hover:-translate-y-0.5 hover:bg-sky-200">
			Login
		</button>
	</form>
@endsection
