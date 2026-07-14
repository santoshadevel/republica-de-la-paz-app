@php
    // Editorial copy used only by this page.
    $values = ['Calidez', 'Comunidad', 'Naturaleza', 'Presencia', 'Bienestar integral'];
    $eventKinds = ['Workshops', 'Charlas', 'Retiros', 'Círculos', 'Encuentros especiales'];
@endphp

<x-layouts.landing :contact="$contact" title="Santosha · República de la Paz">

    {{-- ===================== HERO ===================== --}}
    <section id="top" class="relative px-6 pt-12 pb-14 sm:pt-16 lg:pt-24 lg:pb-24">
        <img src="{{ asset('img/brand/isotipo.png') }}" alt="" aria-hidden="true"
             class="animate-floaty pointer-events-none absolute -top-10 -right-14 w-[min(46vw,560px)] opacity-15">

        <div class="relative mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="mb-5 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">
                    <span class="inline-block h-px w-6 bg-terracotta"></span>República de la Paz
                </span>
                <h1 class="mb-5 text-[clamp(2.5rem,6.4vw,4.75rem)] leading-[1.02] font-semibold tracking-tight text-balance text-ink">
                    Un espacio para volver a habitarte
                </h1>
                <p class="mb-8 max-w-lg text-[clamp(1.0625rem,2vw,1.3125rem)]/relaxed text-ink-soft text-pretty">
                    Yoga, terapias de sonido y acompañamientos individuales conviven bajo una misma intención:
                    crear espacios para el autoconocimiento, la salud y la presencia.
                </p>
                <div class="flex flex-wrap items-center gap-3.5">
                    <a href="#membresias" class="rounded-full bg-terracotta px-7 py-4 text-base font-semibold text-white transition hover:bg-earth">
                        Reservá tu clase de prueba
                    </a>
                    <a href="#acompanamientos" class="inline-flex items-center gap-2 px-2 py-4 text-base font-semibold text-earth transition hover:text-terracotta">
                        Conocé las prácticas <span aria-hidden="true">→</span>
                    </a>
                </div>

                <dl class="mt-11 flex flex-wrap gap-7">
                    <div class="flex flex-col">
                        <dd class="text-3xl font-semibold text-terracotta">2</dd>
                        <dt class="text-sm text-ink-soft">salas en simultáneo</dt>
                    </div>
                    <div class="w-px bg-earth/20" aria-hidden="true"></div>
                    <div class="flex flex-col">
                        <dd class="text-3xl font-semibold text-terracotta">9+</dd>
                        <dt class="text-sm text-ink-soft">disciplinas y terapias</dt>
                    </div>
                    <div class="w-px bg-earth/20" aria-hidden="true"></div>
                    <div class="flex flex-col">
                        <dd class="text-3xl font-semibold text-terracotta">1ra</dd>
                        <dt class="text-sm text-ink-soft">práctica sin costo</dt>
                    </div>
                </dl>
            </div>

            <div class="relative aspect-4/5 overflow-hidden rounded-3xl bg-cream-deep shadow-[0_30px_70px_-30px_rgba(142,70,41,0.5)]">
                <div class="flex h-full items-center justify-center p-8 text-center text-sm text-ink-subtle">
                    Foto: práctica de yoga en luz natural, tonos tierra
                </div>
            </div>
        </div>
    </section>

    {{-- pattern divider --}}
    <div class="h-16 bg-cover bg-center sm:h-24" aria-hidden="true"
         style="background-image:url('{{ asset('img/brand/pattern-terracotta.png') }}')"></div>

    {{-- ===================== LA REPÚBLICA ===================== --}}
    <section id="republica" class="scroll-mt-20 bg-earth px-6 py-16 text-cream sm:py-24 lg:py-28">
        <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div class="relative aspect-5/6 overflow-hidden rounded-3xl bg-earth/60 ring-1 ring-sand/20">
                <div class="flex h-full items-center justify-center p-8 text-center text-sm text-sand-soft/70">
                    Foto: el espacio / la comunidad
                </div>
            </div>
            <div>
                <span class="mb-5 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-sand uppercase">
                    <span class="inline-block h-px w-6 bg-sand"></span>La República
                </span>
                <h2 class="mb-6 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-cream">
                    Una comunidad y un espacio holístico dedicado al bienestar integral
                </h2>
                <p class="mb-4 text-lg/relaxed text-sand-soft text-pretty">
                    Santosha reúne prácticas corporales, energéticas, terapéuticas y de desarrollo personal.
                    Diferentes disciplinas y acompañamientos conviven bajo una misma intención: crear espacios
                    para el autoconocimiento, la salud, la presencia y la conexión humana.
                </p>
                <p class="mb-7 text-lg/relaxed text-sand-soft text-pretty">
                    La llamamos <em class="font-semibold text-sand not-italic">República de la Paz</em> porque
                    creemos en un lugar donde distintas herramientas pueden coexistir y complementarse —
                    sostenidas por la comunidad.
                </p>
                <ul class="flex flex-wrap gap-3">
                    @foreach ($values as $value)
                        <li class="rounded-full border border-sand/40 px-4 py-2 text-sm font-medium text-sand">{{ $value }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ===================== CONSTITUCIÓN ===================== --}}
    <section class="relative overflow-hidden bg-olive px-6 py-16 text-olive-tint sm:py-24 lg:py-28">
        <img src="{{ asset('img/brand/isotipo.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -bottom-20 -left-16 w-[min(40vw,420px)] opacity-10">

        <div class="relative mx-auto mb-12 max-w-3xl text-center">
            <span class="mb-5 inline-block text-xs font-semibold tracking-[0.2em] text-olive-soft uppercase">
                Constitución de la República
            </span>
            <p class="mx-auto mb-3 max-w-2xl text-[clamp(1.1875rem,2.4vw,1.625rem)]/snug font-medium text-olive-tint text-pretty">
                Nosotros, los ciudadanos de la República de la Paz, reunidos por la voluntad de vivir con más
                conciencia, menos ruido y mayor presencia, declaramos esta constitución como una guía para el bienestar.
            </p>
            <p class="mx-auto max-w-xl text-base/relaxed text-olive-soft text-pretty">
                Establecemos estos principios para recuperar aquello que nunca debimos perder: el equilibrio,
                la calma y la conexión con nosotros mismos.
            </p>
        </div>

        <div class="relative mx-auto grid max-w-6xl gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($constitution as $article)
                <article class="rounded-2xl border border-olive-tint/15 bg-cream/5 px-7 py-7">
                    <div class="mb-2 text-xs font-semibold tracking-[0.14em] text-sand uppercase">{{ $article['number'] }}</div>
                    <h3 class="mb-3 text-xl font-semibold text-olive-tint">{{ $article['title'] }}</h3>
                    <p class="text-[0.97rem]/relaxed text-olive-soft text-pretty">{{ $article['body'] }}</p>
                </article>
            @endforeach

            <article class="flex flex-col justify-center rounded-2xl bg-sand px-7 py-7">
                <div class="mb-2 text-xs font-semibold tracking-[0.14em] text-earth uppercase">Disposición final</div>
                <p class="text-[1.03rem]/relaxed font-medium text-[#5a3a22] text-pretty">{{ $finalProvision }}</p>
            </article>
        </div>
    </section>

    {{-- ===================== ACOMPAÑAMIENTOS ===================== --}}
    <section id="acompanamientos" class="scroll-mt-20 bg-cream-deep px-6 py-16 sm:py-24 lg:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="mb-11 max-w-2xl">
                <span class="mb-4 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">
                    <span class="inline-block h-px w-6 bg-terracotta"></span>Consultas y acompañamientos
                </span>
                <h2 class="mb-4 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                    Sesiones individuales y personalizadas
                </h2>
                <p class="text-[17px]/relaxed text-ink-soft text-pretty">
                    Un espacio dedicado a acompañarte de forma cercana, según lo que necesites en cada momento.
                </p>
            </div>

            @if ($therapies->isEmpty())
                <p class="rounded-2xl border border-dashed border-earth/30 px-8 py-10 text-center text-ink-soft">
                    Estamos preparando nuestras sesiones individuales. Escribinos y te contamos.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($therapies as $therapy)
                        <article class="flex flex-col gap-3 rounded-2xl border border-earth/10 bg-cream-card px-6 py-6">
                            <h3 class="text-xl font-semibold text-ink">{{ $therapy->name }}</h3>

                            @if (filled($therapy->description))
                                <p class="flex-1 text-sm/relaxed text-ink-soft text-pretty">{{ $therapy->description }}</p>
                            @else
                                <div class="flex-1"></div>
                            @endif

                            <div class="flex flex-col gap-1 border-t border-earth/10 pt-3 text-[13.5px] text-ink-subtle">
                                @if ($therapy->practitioners->isNotEmpty())
                                    <div>
                                        <span class="font-semibold text-earth">Profesional:</span>
                                        {{ $therapy->practitioners->map->fullName()->join(' · ') }}
                                    </div>
                                @endif
                                @if ($therapy->default_duration_minutes)
                                    <div>
                                        <span class="font-semibold text-earth">Duración:</span>
                                        {{ $therapy->default_duration_minutes }} min
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('login') }}"
                               class="rounded-full border-[1.5px] border-terracotta py-2.5 text-center text-sm font-semibold text-terracotta transition hover:bg-terracotta hover:text-white">
                                Reservar sesión
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== REFERENTES ===================== --}}
    <section id="referentes" class="scroll-mt-20 px-6 py-16 sm:py-24 lg:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="mb-11 max-w-2xl">
                <span class="mb-4 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">
                    <span class="inline-block h-px w-6 bg-terracotta"></span>Referentes de la República
                </span>
                <h2 class="mb-4 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                    Las personas que impulsan Santosha
                </h2>
                <p class="text-[17px]/relaxed text-ink-soft text-pretty">
                    Facilitadoras que sostienen cada práctica con presencia y cercanía.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($practitioners as $practitioner)
                    <article class="flex flex-col gap-4">
                        <div class="relative aspect-4/5 overflow-hidden rounded-2xl bg-cream-deep">
                            @if ($avatar = $practitioner->avatarUrl())
                                <img src="{{ $avatar }}" alt="{{ $practitioner->fullName() }}"
                                     loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-5xl font-semibold text-earth/30">
                                    {{ $practitioner->initials() }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <h3 class="mb-1.5 text-2xl font-semibold text-ink">{{ $practitioner->fullName() }}</h3>

                            @if ($practitioner->activities->isNotEmpty())
                                <div class="mb-3 text-sm font-semibold text-terracotta">
                                    {{ $practitioner->activities->pluck('name')->join(' · ') }}
                                </div>
                            @endif

                            @if (filled($practitioner->bio))
                                <p class="text-[15px]/relaxed text-ink-soft text-pretty">{{ $practitioner->bio }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-9 rounded-2xl border border-dashed border-earth/30 bg-cream-deep px-8 py-7 text-center">
                <h3 class="mb-1.5 text-xl font-semibold text-earth">Ciudadanos de la República</h3>
                <p class="text-[15px] text-ink-soft text-pretty">
                    Un espacio abierto para futuros profesionales, terapeutas y colaboradores que se sumen a la comunidad.
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== MEMBRESÍAS ===================== --}}
    <section id="membresias" class="scroll-mt-20 bg-earth px-6 py-16 text-cream sm:py-24 lg:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <span class="mb-4 inline-block text-xs font-semibold tracking-[0.2em] text-sand uppercase">Membresías y pases</span>
                <h2 class="mb-3.5 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-cream">
                    Elegí cómo habitar la República
                </h2>
                <p class="text-[17px]/relaxed text-sand-soft text-pretty">
                    Prácticas grupales flexibles, con reserva online y acceso a toda la comunidad Santosha.
                </p>
            </div>

            <div class="grid items-stretch gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans as $plan)
                    @php $featured = $plan->isFeatured(); @endphp
                    <div @class([
                        'relative flex flex-col rounded-3xl px-7 py-8',
                        'bg-cream text-ink shadow-[0_24px_60px_-28px_rgba(0,0,0,0.55)]' => $featured,
                        'border border-sand/25 bg-cream/5' => ! $featured,
                    ])>
                        @if ($featured)
                            <span class="absolute top-4 right-4 rounded-full bg-olive px-3 py-1.5 text-[11px] font-semibold tracking-[0.08em] text-olive-tint uppercase">
                                Más elegido
                            </span>
                        @endif

                        <div>
                            <h3 @class(['text-2xl font-semibold', 'text-ink' => $featured, 'text-cream' => ! $featured])>
                                {{ $plan->name }}
                            </h3>

                            <div @class([
                                'mt-1 text-sm font-medium',
                                'text-ink-subtle' => $featured,
                                'text-sand' => ! $featured,
                            ])>
                                @if ($plan->isUnlimited())
                                    Prácticas ilimitadas
                                @elseif ($credits = $plan->credits())
                                    {{ $credits }} {{ \Illuminate\Support\Str::plural('práctica', $credits) }}
                                    @if ($days = $plan->validityDays())
                                        · {{ $days }} días
                                    @endif
                                @endif
                            </div>

                            <div class="mt-4 mb-1 flex items-baseline gap-1.5">
                                <span @class(['text-3xl font-semibold', 'text-ink' => $featured, 'text-cream' => ! $featured])>
                                    {{ $plan->isFree() ? 'Sin costo' : $plan->price->format() }}
                                </span>
                                @if (! $plan->isFree())
                                    <span @class(['text-sm', 'text-ink-subtle' => $featured, 'text-sand-soft' => ! $featured])>/ mes</span>
                                @endif
                            </div>

                            @if (filled($plan->description))
                                <p @class([
                                    'mt-2 text-sm/relaxed text-pretty',
                                    'text-ink-soft' => $featured,
                                    'text-sand-soft' => ! $featured,
                                ])>{{ $plan->description }}</p>
                            @endif
                        </div>

                        @if ($features = $plan->features())
                            <ul class="my-6 flex flex-1 flex-col gap-2.5">
                                @foreach ($features as $feature)
                                    <li @class([
                                        'flex gap-2.5 text-sm/snug',
                                        'text-ink-soft' => $featured,
                                        'text-sand-soft' => ! $featured,
                                    ])>
                                        <span @class([
                                            'flex-none font-semibold',
                                            'text-olive' => $featured,
                                            'text-sand' => ! $featured,
                                        ]) aria-hidden="true">✓</span>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <a href="{{ route('register') }}" @class([
                            'rounded-full py-3 text-center text-sm font-semibold transition',
                            'bg-terracotta text-white hover:bg-earth' => $featured,
                            'border-[1.5px] border-sand/50 text-sand hover:bg-sand hover:text-earth' => ! $featured,
                        ])>
                            {{ $plan->isFree() ? 'Reservar mi clase gratuita' : 'Quiero este pase' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== ASAMBLEA ===================== --}}
    <section id="asamblea" class="scroll-mt-20 px-6 py-16 sm:py-24 lg:py-28">
        <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="mb-4 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">
                    <span class="inline-block h-px w-6 bg-terracotta"></span>Asamblea
                </span>
                <h2 class="mb-4 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                    Nuestros eventos y encuentros
                </h2>
                <p class="mb-7 text-[17px]/relaxed text-ink-soft text-pretty">
                    Momentos especiales para reunirnos, aprender y compartir en comunidad.
                </p>
                <ul class="flex flex-wrap gap-2.5">
                    @foreach ($eventKinds as $kind)
                        <li class="rounded-full bg-cream-deep px-5 py-2.5 text-[15px] font-semibold text-earth">{{ $kind }}</li>
                    @endforeach
                </ul>
                <a href="#contacto" class="mt-7 inline-block rounded-full bg-terracotta px-6 py-3.5 text-[15px] font-semibold text-white transition hover:bg-earth">
                    Consultar próximos eventos
                </a>
            </div>
            <div class="relative aspect-square overflow-hidden rounded-3xl bg-cream-deep shadow-[0_30px_70px_-34px_rgba(142,70,41,0.5)]">
                <div class="flex h-full items-center justify-center p-8 text-center text-sm text-ink-subtle">
                    Foto: círculo / evento en comunidad
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    <section id="faq" class="scroll-mt-20 bg-cream-deep px-6 py-16 sm:py-24 lg:py-28">
        <div class="mx-auto max-w-3xl">
            <div class="mb-11 text-center">
                <span class="mb-4 inline-block text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">Preguntas frecuentes</span>
                <h2 class="text-[clamp(1.875rem,4.4vw,3rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                    Todo lo que querés saber
                </h2>
            </div>

            <div class="flex flex-col gap-10">
                @foreach ($faqGroups as $group)
                    <div>
                        <h3 class="mb-3.5 text-sm font-semibold tracking-[0.12em] text-earth uppercase">{{ $group['group'] }}</h3>
                        <div class="flex flex-col gap-3">
                            @foreach ($group['items'] as $item)
                                <div x-data="{ open: false }" class="overflow-hidden rounded-2xl border border-earth/10 bg-cream-card">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        :aria-expanded="open ? 'true' : 'false'"
                                        class="flex w-full cursor-pointer items-center justify-between gap-4 px-6 py-5 text-left"
                                    >
                                        <span class="text-[17px] font-semibold text-ink">{{ $item['q'] }}</span>
                                        <span class="flex-none text-2xl leading-none text-terracotta transition duration-200"
                                              :class="open && 'rotate-45'" aria-hidden="true">+</span>
                                    </button>
                                    <div x-show="open" x-collapse x-cloak>
                                        <p class="px-6 pb-6 text-[15.5px]/relaxed text-ink-soft text-pretty">{{ $item['a'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== CONTACTO ===================== --}}
    <section id="contacto" class="scroll-mt-20 px-6 py-16 sm:py-24 lg:py-28">
        <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="mb-4 inline-flex items-center gap-2.5 text-xs font-semibold tracking-[0.2em] text-terracotta uppercase">
                    <span class="inline-block h-px w-6 bg-terracotta"></span>Contacto
                </span>
                <h2 class="mb-6 text-[clamp(1.875rem,4.4vw,3.125rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                    Escribinos y sumate a la comunidad
                </h2>
                <p class="mb-7 max-w-md text-[17px]/relaxed text-ink-soft text-pretty">
                    Estamos para acompañarte. Reservá tu clase de prueba o consultanos lo que necesites.
                </p>

                <div class="flex flex-col gap-3.5">
                    @if ($whatsapp = $contact->whatsappUrl('Hola, quiero saber más sobre Santosha.'))
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-3.5 rounded-2xl border border-earth/10 bg-cream-card px-5 py-4 transition hover:border-earth/25">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-olive text-sm font-semibold text-white" aria-hidden="true">Wa</span>
                            <span>
                                <span class="block text-[13px] text-ink-subtle">WhatsApp</span>
                                <span class="font-semibold text-ink">Escribinos directo</span>
                            </span>
                        </a>
                    @endif

                    @if ($instagram = $contact->instagramUrl())
                        <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-3.5 rounded-2xl border border-earth/10 bg-cream-card px-5 py-4 transition hover:border-earth/25">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-terracotta text-sm font-semibold text-white" aria-hidden="true">Ig</span>
                            <span>
                                <span class="block text-[13px] text-ink-subtle">Instagram</span>
                                <span class="font-semibold text-ink">{{ $contact->instagramHandle() }}</span>
                            </span>
                        </a>
                    @endif

                    @if ($email = $contact->email())
                        <a href="mailto:{{ $email }}"
                           class="flex items-center gap-3.5 rounded-2xl border border-earth/10 bg-cream-card px-5 py-4 transition hover:border-earth/25">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-ink text-sm font-semibold text-white" aria-hidden="true">@</span>
                            <span>
                                <span class="block text-[13px] text-ink-subtle">Email</span>
                                <span class="font-semibold text-ink">{{ $email }}</span>
                            </span>
                        </a>
                    @endif

                    @if ($location = $contact->location())
                        <div class="flex items-center gap-3.5 rounded-2xl border border-earth/10 bg-cream-card px-5 py-4">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-earth text-white" aria-hidden="true">◎</span>
                            <span>
                                <span class="block text-[13px] text-ink-subtle">Ubicación</span>
                                <span class="font-semibold text-ink">{{ $location }}</span>
                            </span>
                        </div>
                    @endif
                </div>

                @if ($map = $contact->mapEmbedUrl())
                    <div class="mt-4 overflow-hidden rounded-2xl border border-earth/10">
                        <iframe src="{{ $map }}" title="Ubicación de {{ config('app.name') }}" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade" class="h-64 w-full border-0"></iframe>
                    </div>
                @endif
            </div>

            {{-- The form never hits the server: it hands the visitor to WhatsApp
                 with the message prefilled (decision recorded in the SPEC). --}}
            @if ($whatsappNumber = $contact->whatsappNumber())
                <div
                    x-data="{
                        name: '',
                        message: '',
                        get waUrl() {
                            const text = `Hola, soy ${this.name.trim()}. ${this.message.trim()}`
                            return `https://wa.me/{{ $whatsappNumber }}?text=${encodeURIComponent(text)}`
                        },
                    }"
                    class="rounded-3xl border border-earth/10 bg-cream-card p-7 sm:p-10"
                >
                    <h3 class="mb-1 text-2xl font-semibold text-ink">Escribinos por WhatsApp</h3>
                    <p class="mb-6 text-sm/relaxed text-ink-soft">
                        Completá y te abrimos el chat con el mensaje listo para enviar.
                    </p>

                    <form @submit.prevent="window.open(waUrl, '_blank', 'noopener')" class="flex flex-col gap-4">
                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-ink-soft">
                            Nombre y apellido
                            <input type="text" x-model="name" required autocomplete="name"
                                   class="rounded-xl border border-earth/20 bg-white px-4 py-3 font-normal text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20">
                        </label>

                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-ink-soft">
                            ¿En qué te acompañamos?
                            <textarea rows="4" x-model="message" required
                                      class="resize-y rounded-xl border border-earth/20 bg-white px-4 py-3 font-normal text-ink outline-none focus:border-terracotta focus:ring-2 focus:ring-terracotta/20"></textarea>
                        </label>

                        <button type="submit"
                                :disabled="! name.trim() || ! message.trim()"
                                class="cursor-pointer rounded-full bg-terracotta py-4 text-base font-semibold text-white transition hover:bg-earth disabled:cursor-not-allowed disabled:opacity-50">
                            Abrir WhatsApp
                        </button>

                        <p class="text-center text-[13px] text-ink-subtle">
                            Se abre WhatsApp con tu mensaje escrito. Vos lo enviás.
                        </p>
                    </form>
                </div>
            @endif
        </div>
    </section>

</x-layouts.landing>
