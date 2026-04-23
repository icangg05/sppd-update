@extends('layouts.guest')

@section('title', 'Login')

@section('content')

	@if ($errors->any())
		<div class="alert alert-danger text-center"
			style="border-radius: 10px; font-size: 14px; padding: 10px; margin-bottom: 25px;">
			@foreach ($errors->all() as $error)
				<p class="text-sm text-red-700">{{ $error }}</p>
			@endforeach
		</div>
	@endif

	<form action="{{ route('login') }}" method="post">
		@csrf
		<div class="form-group floating-group">
			<input type="text" class="form-control floating-control" name="username" id="username" placeholder=""
				value="{{ old('username') }}" required autofocus>
			<label for="username" class="floating-label">Username</label>
		</div>

		<div class="form-group floating-group">
			<input type="password" class="form-control floating-control" name="password" id="password" placeholder=""
				required value="password">
			<label for="password" class="floating-label">Password</label>
		</div>

		<div class="row">
			<div class="col-xs-12">
				<button type="submit" class="btn btn-primary btn-block btn-modern">LOGIN</button>
			</div>
		</div>
	</form>

@endsection
