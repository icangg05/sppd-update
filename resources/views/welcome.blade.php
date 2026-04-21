<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

	<!-- Styles / Scripts -->
	@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
		@vite(['resources/css/app.css', 'resources/js/app.js'])
	@endif
</head>

<body>

	<p class="text-base p-10 mx-auto max-w-4xl">Lorem ipsum dolor sit amet consectetur adipisicing elit. Repudiandae
		obcaecati, quis
		distinctio ipsum
		culpa earum
		excepturi aspernatur vel accusantium quos. Maxime animi laboriosam deleniti dolore ex cum repellendus possimus
		molestias?</p>

</body>

</html>
