{{--
    Layout of the student portal and the auth screens. Shares the brand tokens
    with the public landing (resources/css/app.css) but has its own header: the
    landing navigates to anchors, this one to portal routes.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- General Sans is a Fontshare face, so it cannot go through the bunny()
         self-hosting used for the panel's Instrument Sans. --}}
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-full bg-cream font-brand text-ink antialiased">

@php
    $isStudent = auth()->check() && auth()->user()->isStudent();

    $portalLinks = $isStudent ? [
        'portal.dashboard' => 'Mi espacio',
        'portal.schedule' => 'Reservar',
        'portal.plans' => 'Pases',
    ] : [];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-earth/10 bg-cream/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
        <a href="{{ url('/') }}" class="flex flex-none items-center" aria-label="{{ config('app.name') }}">
            <img src="{{ asset('img/brand/logo-color.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
        </a>

        {{-- desktop --}}
        <div class="hidden items-center gap-5 md:flex">
            @guest
                <a href="{{ route('login') }}"
                   @class([
                       'text-[15px] font-semibold transition',
                       'text-terracotta' => request()->routeIs('login'),
                       'text-earth hover:text-terracotta' => ! request()->routeIs('login'),
                   ])>Ingresar</a>
                <a href="{{ route('register') }}" class="rounded-full bg-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-earth">
                    Registrarme
                </a>
            @else
                @if ($isStudent)
                    <nav class="flex gap-5 whitespace-nowrap">
                        @foreach ($portalLinks as $route => $label)
                            <a href="{{ route($route) }}"
                               @class([
                                   'text-[15px] transition',
                                   'font-semibold text-terracotta' => request()->routeIs($route),
                                   'font-medium text-ink-muted hover:text-terracotta' => ! request()->routeIs($route),
                               ])>{{ $label }}</a>
                        @endforeach
                    </nav>
                @else
                    {{-- Staff cannot enter the portal; keep a way back to the panel. --}}
                    <a href="/admin" class="text-[15px] font-semibold text-earth transition hover:text-terracotta">Ir al panel</a>
                @endif

                <div class="flex items-center gap-3.5 border-l border-earth/15 pl-4">
                    <span class="text-sm text-ink-soft">
                        Hola, <strong class="font-semibold text-ink">{{ Str::of(auth()->user()->name)->trim()->explode(' ')->first() }}</strong>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer text-[15px] font-semibold text-earth transition hover:text-terracotta">Salir</button>
                    </form>
                </div>
            @endguest
        </div>

        {{-- mobile toggle: only worth showing when there is a nav to open --}}
        @if ($isStudent)
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open ? 'true' : 'false'"
                aria-controls="portal-nav"
                aria-label="Menú"
                class="flex h-10 w-10 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg md:hidden"
            >
                <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && 'translate-y-2 rotate-45'"></span>
                <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && 'opacity-0'"></span>
                <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && '-translate-y-2 -rotate-45'"></span>
            </button>
        @else
            <div class="flex items-center gap-3 md:hidden">
                @guest
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-earth">Ingresar</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-terracotta px-4 py-2 text-sm font-semibold text-white">Registrarme</a>
                @else
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer text-sm font-semibold text-earth">Salir</button>
                    </form>
                @endguest
            </div>
        @endif
    </div>

    @if ($isStudent)
        <div
            id="portal-nav"
            x-show="open"
            x-cloak
            x-collapse
            @click.outside="open = false"
            class="border-t border-earth/10 bg-cream px-5 pt-2.5 pb-5 md:hidden"
        >
            <nav class="flex flex-col">
                @foreach ($portalLinks as $route => $label)
                    <a href="{{ route($route) }}" @click="open = false"
                       @class([
                           'border-b border-earth/[0.08] py-3.5 text-[17px]',
                           'font-semibold text-terracotta' => request()->routeIs($route),
                           'font-medium text-ink' => ! request()->routeIs($route),
                       ])>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="pt-3.5">
                @csrf
                <button type="submit" class="cursor-pointer text-[17px] font-medium text-earth">Salir</button>
            </form>
        </div>
    @endif
</header>

<main>
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
