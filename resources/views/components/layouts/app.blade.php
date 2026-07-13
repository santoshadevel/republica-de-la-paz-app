<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-stone-50 text-stone-800 antialiased">
    <header class="border-b border-stone-200 bg-white/80 backdrop-blur">
        <nav class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-stone-900">
                {{ config('app.name') }}
            </a>
            <div class="flex items-center gap-4 text-sm">
                @auth
                    @if (auth()->user()->isStudent())
                        <a href="{{ route('portal.dashboard') }}" class="text-stone-600 hover:text-stone-900">Mi espacio</a>
                        <a href="{{ route('portal.schedule') }}" class="text-stone-600 hover:text-stone-900">Reservar</a>
                        <a href="{{ route('portal.plans') }}" class="text-stone-600 hover:text-stone-900">Pases</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-stone-500 hover:text-stone-900">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-stone-600 hover:text-stone-900">Ingresar</a>
                    <a href="{{ route('register') }}"
                       class="rounded-full bg-stone-900 px-4 py-1.5 font-medium text-white hover:bg-stone-700">
                        Registrarme
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
