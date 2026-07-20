@props(['contact'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Una comunidad y un espacio holístico dedicado al bienestar integral: yoga, terapias de sonido y acompañamientos individuales.' }}">

    {{-- General Sans is a Fontshare face, so it is not available through the
         bunny() self-hosting used for the panel's Instrument Sans. --}}
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine (and its collapse plugin) ships with Livewire's bundle; the
         landing has no Livewire component but reuses it rather than pulling in
         alpinejs as a separate dependency. --}}
    @livewireStyles

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-full bg-cream font-brand text-ink antialiased">

@php
    $navLinks = [
        '#republica' => 'La República',
        '#acompanamientos' => 'Acompañamientos',
        '#referentes' => 'Referentes',
        '#membresias' => 'Membresías',
        '#asamblea' => 'Asamblea',
        '#faq' => 'FAQ',
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-earth/10 bg-cream/85 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5">
        <a href="#top" class="flex flex-none items-center" aria-label="{{ config('app.name') }}">
            <img src="{{ asset('img/brand/logo-color.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto sm:h-11">
        </a>

        <div class="hidden items-center gap-5 lg:flex">
            <nav class="flex gap-5 whitespace-nowrap">
                @foreach ($navLinks as $href => $label)
                    <a href="{{ $href }}" class="text-[15px] font-medium text-ink-muted transition hover:text-terracotta">{{ $label }}</a>
                @endforeach
            </nav>
            @guest
                <a href="{{ route('login') }}" class="text-[15px] font-semibold text-earth transition hover:text-terracotta">Ingresar</a>
            @else
                <a href="{{ route('portal.dashboard') }}" class="text-[15px] font-semibold text-earth transition hover:text-terracotta">Mi espacio</a>
            @endguest
            <a href="#membresias" class="rounded-full bg-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-earth">
                Clase gratuita
            </a>
        </div>

        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-controls="mobile-nav"
            aria-label="Menú"
            class="flex h-10 w-10 flex-col items-center justify-center gap-1.5 rounded-lg lg:hidden"
        >
            <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && 'translate-y-2 rotate-45'"></span>
            <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && 'opacity-0'"></span>
            <span class="block h-0.5 w-6 rounded-full bg-ink transition duration-200" :class="open && '-translate-y-2 -rotate-45'"></span>
        </button>
    </div>

    <div
        id="mobile-nav"
        x-show="open"
        x-cloak
        x-collapse
        @click.outside="open = false"
        class="border-t border-earth/10 bg-cream px-5 pt-3 pb-6 lg:hidden"
    >
        <nav class="flex flex-col">
            @foreach ($navLinks as $href => $label)
                <a href="{{ $href }}" @click="open = false"
                   class="border-b border-earth/[0.08] py-3.5 text-[17px] font-medium text-ink">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="mt-4 flex gap-3">
            @guest
                <a href="{{ route('login') }}" @click="open = false"
                   class="flex-1 rounded-full border-[1.5px] border-earth/30 py-3 text-center text-[15px] font-semibold text-earth">Ingresar</a>
            @else
                <a href="{{ route('portal.dashboard') }}" @click="open = false"
                   class="flex-1 rounded-full border-[1.5px] border-earth/30 py-3 text-center text-[15px] font-semibold text-earth">Mi espacio</a>
            @endguest
            <a href="#membresias" @click="open = false"
               class="flex-1 rounded-full bg-terracotta py-3 text-center text-[15px] font-semibold text-white">Clase gratuita</a>
        </div>
    </div>
</header>

<main>
    {{ $slot }}
</main>

<footer class="bg-ink px-6 pt-14 pb-10 text-sand-soft sm:pt-20">
    <div class="mx-auto grid max-w-6xl gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <img src="{{ asset('img/brand/isotipo-sand.png') }}" alt="" aria-hidden="true" class="mb-4 h-12 w-auto opacity-90">
            <p class="max-w-70 text-sm/relaxed text-sand-dim">
                Una comunidad viva donde distintos caminos de bienestar se encuentran.
            </p>
        </div>

        <div>
            <div class="mb-4 text-xs font-semibold tracking-[0.12em] text-sand uppercase">Explorar</div>
            <div class="flex flex-col gap-2.5">
                @foreach ($navLinks as $href => $label)
                    <a href="{{ $href }}" class="text-[15px] transition hover:text-sand">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div>
            <div class="mb-4 text-xs font-semibold tracking-[0.12em] text-sand uppercase">Comunidad</div>
            <div class="flex flex-col gap-2.5">
                @if ($url = $contact->instagramUrl())
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-[15px] transition hover:text-sand">Instagram</a>
                @endif
                @if ($url = $contact->whatsappUrl())
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-[15px] transition hover:text-sand">WhatsApp</a>
                @endif
                @if ($email = $contact->email())
                    <a href="mailto:{{ $email }}" class="text-[15px] transition hover:text-sand">{{ $email }}</a>
                @endif
                <a href="#contacto" class="text-[15px] transition hover:text-sand">Contacto</a>
            </div>
        </div>

        <div>
            <div class="mb-4 text-xs font-semibold tracking-[0.12em] text-sand uppercase">Tu espacio</div>
            <div class="flex flex-col gap-2.5">
                @guest
                    <a href="{{ route('login') }}" class="text-[15px] transition hover:text-sand">Ingresar</a>
                    <a href="{{ route('register') }}" class="text-[15px] transition hover:text-sand">Registrarme</a>
                @else
                    <a href="{{ route('portal.dashboard') }}" class="text-[15px] transition hover:text-sand">Mi espacio</a>
                    <a href="{{ route('portal.schedule') }}" class="text-[15px] transition hover:text-sand">Reservar</a>
                @endguest
            </div>
        </div>
    </div>

    <div class="mx-auto mt-12 flex max-w-6xl flex-col gap-2 border-t border-sand-soft/15 pt-6 text-[13px] text-sand-dim sm:flex-row sm:items-center sm:justify-between">
        <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
        @if ($location = $contact->location())
            <span>{{ $location }}</span>
        @endif
    </div>
</footer>

@livewireScripts
</body>
</html>
