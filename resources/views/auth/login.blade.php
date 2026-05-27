@extends('layouts.guest')

@section('title', 'Login')

@section('content')
	@if ($errors->any())
		<div class="rounded-2xl border border-rose-200/80 bg-rose-100/80 px-4 py-3 text-sm text-rose-700 backdrop-blur-md">
			@foreach ($errors->all() as $error)
				<p>{{ $error }}</p>
			@endforeach
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
				value="{{ old('username', 'admin_kominfo') }}"
				required
				autofocus
				class="mt-2 w-full rounded-2xl border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan username">
		</div>

		<div>
			<label for="password" class="block text-sm font-semibold text-sky-50">Password</label>
			<input
				value="admin"
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
