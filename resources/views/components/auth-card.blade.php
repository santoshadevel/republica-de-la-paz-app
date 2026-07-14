@props([
    'eyebrow',
    'heading',
    'lead',
    'tone' => 'earth', // earth = ingresar · olive = registrarse
])

{{-- Split card of the auth screens: brand panel + form panel. --}}
<section class="mx-auto max-w-5xl px-5 py-6 pb-16 sm:py-8">
    <div class="grid overflow-hidden rounded-3xl bg-cream-card shadow-[0_40px_90px_-50px_rgba(142,70,41,0.7)] lg:grid-cols-2">

        <div @class([
            'relative flex min-h-85 flex-col justify-between overflow-hidden p-9 sm:p-12',
            'bg-earth text-cream' => $tone === 'earth',
            'bg-olive text-olive-tint' => $tone === 'olive',
        ])>
            <img src="{{ asset('img/brand/isotipo.png') }}" alt="" aria-hidden="true"
                 class="pointer-events-none absolute -right-12 -bottom-12 w-70 opacity-15">

            <div class="relative">
                <span class="mb-4.5 inline-block text-xs font-semibold tracking-[0.18em] text-sand uppercase">{{ $eyebrow }}</span>
                <h1 @class([
                    'mb-3.5 text-[clamp(1.75rem,3.6vw,2.5rem)] leading-tight font-semibold text-balance',
                    'text-cream' => $tone === 'earth',
                    'text-olive-tint' => $tone === 'olive',
                ])>{{ $heading }}</h1>
                <p @class([
                    'max-w-85 text-base/relaxed text-pretty',
                    'text-sand-soft' => $tone === 'earth',
                    'text-olive-soft' => $tone === 'olive',
                ])>{{ $lead }}</p>
            </div>

            @isset($aside)
                <div class="relative mt-8">
                    {{ $aside }}
                </div>
            @endisset
        </div>

        <div class="p-8 sm:p-12">
            {{ $slot }}
        </div>
    </div>
</section>
