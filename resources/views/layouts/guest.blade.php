<!doctype html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<meta name="google" content="notranslate">
	<meta name="robots" content="noindex, nofollow">
	<title>
		@hasSection('title')
			@yield('title') -
		@endif{{ config('app.name') }}
	</title>
	<link rel="icon" href="{{ asset('assets2/dist/img/user.png') }}">
	<!-- Bootstrap 3.3.6 -->
	<link rel="stylesheet" href="{{ asset('assets2/bootstrap/css/bootstrap.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets2/dist/css/AdminLTE.css') }}">
	<link rel="stylesheet" href="{{ asset('assets2/plugins/iCheck/css/blue.css') }}">
	<link rel="stylesheet"
		href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
	<link href="https://fonts.googleapis.com/css?family=Anton|Permanent+Marker|Quicksand" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600&display=swap"
		rel="stylesheet">

	<style>
		body {
			font-family: 'Poppins', sans-serif;
			height: 100vh;
			overflow: hidden;
			margin: 0;
		}

		#video-background {
			position: fixed;
			right: 0;
			bottom: 0;
			min-width: 100%;
			min-height: 100%;
			width: auto;
			height: auto;
			z-index: -100;
			object-fit: cover;
		}

		.login-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(135deg, rgba(41, 128, 185, 0.6) 0%, rgba(109, 213, 250, 0.6) 100%);
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.login-container {
			width: 100%;
			max-width: 900px;
			padding: 15px;
		}

		.login-card {
			background: transparent;
			border-radius: 20px;
			box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
			overflow: hidden;
			display: flex;
			flex-wrap: wrap;
		}

		.login-left {
			flex: 1;
			padding: 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			text-align: center;
			min-width: 300px;
			background: transparent !important;
			box-shadow: none !important;
			border: none !important;
		}

		.login-right {
			flex: 1;
			padding: 50px 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			min-width: 300px;
			background: rgba(255, 255, 255, 0.2);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
		}

		.brand-logos {
			margin-bottom: 20px;
		}

		.brand-logos img {
			margin: 0 10px;
			transition: transform 0.3s ease;
			filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
		}

		.brand-logos img:hover {
			transform: scale(1.05);
		}

		.app-title {
			font-weight: 700;
			font-size: 24px;
			color: #000000;
			margin-top: 10px;
			line-height: 1.3;
			letter-spacing: 0.5px;
			text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
		}

		.app-subtitle {
			font-size: 14px;
			color: #000000;
			margin-top: 8px;
			font-weight: 400;
			text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
		}

		.login-header {
			margin-bottom: 35px;
			text-align: center;
		}

		.login-header h3 {
			font-weight: 600;
			color: #2c3e50;
			margin: 0 0 10px 0;
			font-size: 22px;
		}

		.login-header p {
			color: #000000;
			font-size: 14px;
			margin: 0;
		}

		/* Floating Label Styles */
		.form-group.floating-group {
			position: relative;
			margin-bottom: 25px;
		}

		.form-control.floating-control {
			height: 52px;
			padding: 25px 15px 10px 15px;
			border: 2px solid #eef2f7;
			border-radius: 12px;
			font-size: 15px;
			background: #fdfdfd;
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			box-shadow: none;
			color: #34495e;
		}

		.form-control.floating-control:focus {
			border-color: #3c8dbc;
			background: #fff;
			outline: none;
			box-shadow: 0 0 0 4px rgba(60, 141, 188, 0.1);
		}

		.floating-label {
			position: absolute;
			top: 15px;
			left: 17px;
			font-size: 15px;
			color: #95a5a6;
			pointer-events: none;
			transition: all 0.2s ease;
			background: transparent;
			font-weight: 400;
		}

		.form-control.floating-control:focus~.floating-label,
		.form-control.floating-control:not(:placeholder-shown)~.floating-label {
			top: 8px;
			left: 15px;
			font-size: 11px;
			font-weight: 600;
			color: #3c8dbc;
			transform: translateY(0);
		}

		.btn-modern {
			height: 52px;
			border-radius: 12px;
			font-weight: 600;
			font-size: 16px;
			letter-spacing: 1px;
			text-transform: uppercase;
			background: linear-gradient(135deg, #3c8dbc 0%, #2980b9 100%);
			border: none;
			box-shadow: 0 10px 20px rgba(60, 141, 188, 0.3);
			transition: all 0.3s;
			margin-top: 10px;
		}

		.btn-modern:hover {
			transform: translateY(-2px);
			box-shadow: 0 15px 25px rgba(60, 141, 188, 0.4);
			background: linear-gradient(135deg, #4fa3d4 0%, #3498db 100%);
		}

		.btn-modern:active {
			transform: translateY(0);
			box-shadow: 0 5px 15px rgba(60, 141, 188, 0.3);
		}

		.copyright {
			margin-top: 40px;
			font-size: 12px;
			color: #000000;
			text-align: center;
		}

		.copyright a {
			color: #3c8dbc;
			text-decoration: none;
			transition: color 0.2s;
		}

		.copyright a:hover {
			color: #3c8dbc;
		}

		@media (max-width: 768px) {
			body {
				height: auto;
				overflow-y: auto;
			}

			.login-overlay {
				position: relative;
				height: auto;
				min-height: 100vh;
				padding: 40px 15px;
				align-items: center;
				display: flex;
			}

			.login-card {
				flex-direction: column;
				max-width: 400px;
				margin: 0 auto;
			}

			.login-left {
				padding: 20px;
				min-height: auto;
			}

			.login-right {
				padding: 30px 20px;
			}

			.app-title {
				font-size: 20px;
			}

			.brand-logos {
				display: none;
			}
		}
	</style>
</head>

<body class="hold-transition">
	<video autoplay muted loop id="video-background">
		<source src="{{ asset('assets2/dist/img/background.webm') }}" type="video/webm">
		Your browser does not support HTML5 video.
	</video>
	<div class="login-overlay">
		<div class="login-container">
			<div class="login-card">
				<div class="login-left">
					<img src="{{ asset('assets/img/HERO.webp') }}" alt="Hero Image"
						style="max-width: 80%; margin-bottom: 30px; border-radius: 10px;">
					<div class="brand-logos">
						<img src="{{ asset('assets/img/logokdi.png') }}" alt="Logo Kendari" width="50">
						<img src="{{ asset('assets/img/BSE.png') }}" alt="Logo BSE" width="120">
					</div>
					<div class="app-title">SPPD ELEKTRONIK<br>KOTA KENDARI</div>
					<div class="app-subtitle">Sistem Informasi Perjalanan Dinas</div>
				</div>

				<div class="login-right">
					<div class="login-header">
						<h3>Selamat Datang</h3>
						<p>Silahkan login untuk memulai sesi anda</p>
					</div>

					@yield('content')

					<div class="copyright">
						V 3.0 | &copy; <?= date('Y') ?> Pemerintah Kota Kendari
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- jQuery 2.2.3 -->
	<script src="{{ asset('assets2/plugins/jQuery/jquery-2.2.3.min.js') }}"></script>
	<!-- Bootstrap 3.3.6 -->
	<script src="{{ asset('assets2/bootstrap/js/bootstrap.min.js') }}"></script>
	<script src="{{ asset('assets2/plugins/iCheck/js/icheck.min.js') }}"></script>
	<script>
		$(function() {
			// Optional: Keep iCheck if needed for other inputs, but currently no checkboxes in new form
			$('input[type="checkbox"]').iCheck({
				checkboxClass: 'icheckbox_square-blue',
				radioClass: 'iradio_square-blue',
				increaseArea: '20%'
			});
		});
	</script>
</body>

</html>
