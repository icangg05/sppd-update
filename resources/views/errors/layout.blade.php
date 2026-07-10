<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    {{-- Sesuaikan dengan cara Anda memuat CSS (Vite/Mix) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="relative flex min-h-screen items-center justify-center bg-slate-50 overflow-hidden font-sans">

    {{-- Ornamen Latar Belakang (Glassmorphism Effect) --}}
    <div class="absolute -top-20 -left-20 h-96 w-96 rounded-full bg-emerald-400/40 mix-blend-multiply blur-[80px] animate-pulse"></div>
    <div class="absolute -bottom-20 -right-20 h-96 w-96 rounded-full bg-cyan-400/40 mix-blend-multiply blur-[80px] animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300/30 mix-blend-multiply blur-[80px] animate-pulse" style="animation-delay: 4s;"></div>

    {{-- Panel Utama (Glass Panel) --}}
    <div class="relative z-10 w-full max-w-md mx-4 rounded border border-white/60 bg-white/60 p-10 text-center shadow-2xl backdrop-blur-xl">
        @yield('content')
    </div>

</body>
</html>
