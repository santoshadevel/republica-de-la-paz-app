<div>
    <section class="mx-auto max-w-6xl px-5 py-8 pb-18 sm:py-12">

        <div class="mb-6 max-w-2xl">
            <span class="mb-3.5 inline-flex items-center gap-2.5 text-[13px] font-semibold tracking-[0.18em] text-terracotta uppercase">
                <span class="inline-block h-px w-6 bg-terracotta"></span>Mi espacio
            </span>
            <h1 class="text-[clamp(1.75rem,4vw,2.75rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                Hola{{ $student ? ', '.$student->first_name : '' }}
            </h1>
            <p class="mt-2 text-[17px]/relaxed text-ink-soft">Este es tu espacio en la comunidad.</p>
        </div>

        {{-- MI PASE --}}
        @if ($membership)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-earth px-6 py-5.5 text-cream">
                <div class="flex flex-wrap items-center gap-7">
                    <div>
                        <div class="mb-1 text-[12.5px] font-semibold tracking-[0.12em] text-sand uppercase">Tu pase</div>
                        <div class="text-xl font-semibold">{{ $membership->plan?->name ?? 'Pase' }}</div>
                    </div>

                    <div class="h-9.5 w-px bg-sand/30" aria-hidden="true"></div>

                    <div>
                        <div class="mb-1 text-[12.5px] font-semibold tracking-[0.12em] text-sand uppercase">Prácticas disponibles</div>
                        <div class="text-xl font-semibold">
                            {{ $membership->is_unlimited ? 'Ilimitadas' : $membership->creditsRemaining() }}
                        </div>
                    </div>

                    <div class="h-9.5 w-px bg-sand/30" aria-hidden="true"></div>

                    <div>
                        <div class="mb-1 text-[12.5px] font-semibold tracking-[0.12em] text-sand uppercase">
                            {{ $membership->isCurrentlyActive() ? 'Vence' : 'Venció' }}
                        </div>
                        <div class="text-xl font-semibold">
                            {{ $membership->ends_at?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                </div>

                <a href="{{ route('portal.schedule') }}"
                   class="flex-none rounded-full bg-sand px-5.5 py-3 text-sm font-semibold text-[#5a3a22] transition hover:bg-sand-soft">
                    Reservar una práctica
                </a>
            </div>
        @else
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4.5 rounded-3xl border border-dashed border-earth/35 bg-cream-card px-7 py-6.5">
                <div class="flex items-center gap-4.5">
                    <div class="flex size-13 flex-none items-center justify-center rounded-full bg-terracotta/12 text-2xl text-terracotta" aria-hidden="true">◔</div>
                    <div>
                        <div class="mb-0.5 text-[19px] font-semibold text-ink">Todavía no tenés un pase activo</div>
                        <div class="text-[15px] text-ink-soft">Solicitá un pase para empezar a reservar tus prácticas.</div>
                    </div>
                </div>
                <a href="{{ route('portal.plans') }}"
                   class="flex-none rounded-full bg-terracotta px-6.5 py-3.5 text-[15px] font-semibold text-white transition hover:bg-earth">
                    Ver pases
                </a>
            </div>
        @endif

        {{-- PRÓXIMAS --}}
        <h2 class="mb-4 text-[clamp(1.375rem,3vw,1.75rem)] font-semibold text-ink">Mis próximas reservas</h2>

        <div class="mb-12 flex flex-col gap-3">
            @forelse ($agenda['upcoming'] as $entry)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-earth/12 bg-cream-card px-5 py-4">
                    <div>
                        <p class="font-semibold text-ink">{{ $entry['title'] }}</p>
                        <p class="text-sm text-ink-subtle">{{ $entry['type'] }} · {{ $entry['status'] }}</p>
                    </div>
                    <div class="flex-none text-right">
                        <p class="font-semibold text-terracotta">{{ $entry['starts_at']?->isoFormat('ddd D/MM') }}</p>
                        <p class="text-sm text-ink-soft">{{ $entry['starts_at']?->format('H:i') }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-earth/30 bg-cream-card px-6 py-11 text-center">
                    <p class="mb-3.5 text-[15px] text-ink-soft">No tenés nada agendado todavía.</p>
                    <a href="{{ route('portal.schedule') }}"
                       class="inline-block rounded-full bg-terracotta px-6 py-3 text-sm font-semibold text-white transition hover:bg-earth">
                        Ver el calendario
                    </a>
                </div>
            @endforelse
        </div>

        {{-- HISTORIAL --}}
        @if (! empty($agenda['past']))
            <h2 class="mb-4 text-[clamp(1.375rem,3vw,1.75rem)] font-semibold text-ink">Historial</h2>

            <div class="overflow-hidden rounded-3xl border border-earth/12 bg-cream-card">
                @foreach (array_slice($agenda['past'], 0, 8) as $entry)
                    <div @class([
                        'flex items-center justify-between gap-4 px-5 py-3.5',
                        'border-t border-earth/[0.08]' => ! $loop->first,
                    ])>
                        <div>
                            <span class="text-[15px] text-ink">{{ $entry['title'] }}</span>
                            <span class="ml-2 text-[13px] text-ink-subtle">{{ $entry['status'] }}</span>
                        </div>
                        <span class="flex-none text-sm text-ink-subtle">{{ $entry['starts_at']?->format('d/m/Y') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
