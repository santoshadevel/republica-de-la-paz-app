<div>
    <section class="mx-auto max-w-6xl px-5 py-8 pb-18 sm:py-12">

        <div class="mb-3.5 max-w-2xl">
            <span class="mb-3.5 inline-flex items-center gap-2.5 text-[13px] font-semibold tracking-[0.18em] text-terracotta uppercase">
                <span class="inline-block h-px w-6 bg-terracotta"></span>Pases de la República
            </span>
            <h1 class="text-[clamp(1.75rem,4vw,2.75rem)] leading-tight font-semibold tracking-tight text-balance text-ink">
                Elegí tu pase y solicitalo
            </h1>
        </div>

        <div class="mb-8 flex max-w-3xl items-start gap-3.5 rounded-2xl border border-earth/15 bg-cream-card px-5 py-4">
            <span class="flex size-6.5 flex-none items-center justify-center rounded-full bg-olive/15 text-sm font-bold text-olive" aria-hidden="true">i</span>
            <p class="text-[14.5px]/relaxed text-ink-muted text-pretty">
                <strong class="text-ink">No se paga online.</strong> Solicitás el pase acá y el equipo de Santosha lo revisa,
                lo aprueba y lo activa manualmente. Coordinamos el pago con vos al aprobarlo.
            </p>
        </div>

        {{-- CATÁLOGO --}}
        <div class="mb-14 grid gap-4.5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($plans as $plan)
                @php
                    $featured = $plan->isFeatured();
                    $pendingHere = $pendingPlanIds->contains($plan->id);
                @endphp

                <div @class([
                    'relative flex flex-col rounded-3xl px-6 py-7',
                    'bg-earth text-cream shadow-[0_24px_60px_-30px_rgba(142,70,41,0.9)]' => $featured,
                    'border border-earth/15 bg-cream-card' => ! $featured,
                ])>
                    @if ($featured)
                        <span class="absolute top-4 right-4 rounded-full bg-olive px-3 py-1.5 text-[11px] font-semibold tracking-[0.06em] text-olive-tint uppercase">
                            Más elegido
                        </span>
                    @endif

                    <h2 @class(['text-xl font-semibold', 'text-cream' => $featured, 'text-ink' => ! $featured])>
                        {{ $plan->name }}
                    </h2>

                    <div @class([
                        'mt-1 text-sm font-medium',
                        'text-sand' => $featured,
                        'text-ink-subtle' => ! $featured,
                    ])>
                        @if ($plan->isUnlimited())
                            Prácticas ilimitadas
                        @elseif ($credits = $plan->credits())
                            {{ $credits }} {{ Str::plural('práctica', $credits) }} por mes
                        @endif
                    </div>

                    @if (filled($plan->description))
                        <p @class([
                            'mt-3 text-sm/relaxed text-pretty',
                            'text-sand-soft' => $featured,
                            'text-ink-soft' => ! $featured,
                        ])>{{ $plan->description }}</p>
                    @endif

                    <div @class([
                        'mt-4 flex flex-1 flex-col gap-2 border-t pt-4 text-[13.5px]',
                        'border-sand/25 text-sand-soft' => $featured,
                        'border-earth/12 text-ink-soft' => ! $featured,
                    ])>
                        @foreach ($plan->features() as $feature)
                            <div class="flex items-center gap-2">
                                <span @class([
                                    'size-1.5 flex-none rounded-full',
                                    'bg-sand' => $featured,
                                    'bg-olive' => ! $featured,
                                ]) aria-hidden="true"></span>
                                {{ $feature }}
                            </div>
                        @endforeach

                        @if ($days = $plan->validityDays())
                            <div class="flex items-center gap-2">
                                <span @class([
                                    'size-1.5 flex-none rounded-full',
                                    'bg-sand' => $featured,
                                    'bg-olive' => ! $featured,
                                ]) aria-hidden="true"></span>
                                Vigencia {{ $days }} días
                            </div>
                        @endif
                    </div>

                    <div @class([
                        'mt-4 border-t pt-4',
                        'border-sand/25' => $featured,
                        'border-earth/12' => ! $featured,
                    ])>
                        <span @class(['text-2xl font-semibold', 'text-cream' => $featured, 'text-ink' => ! $featured])>
                            {{ $plan->isFree() ? 'Sin costo' : $plan->price->format() }}
                        </span>
                    </div>

                    <button
                        wire:click="requestPlan({{ $plan->id }})"
                        wire:loading.attr="disabled"
                        wire:target="requestPlan({{ $plan->id }})"
                        @disabled($pendingHere)
                        @class([
                            'mt-4 rounded-full py-3 text-sm font-semibold transition',
                            'cursor-not-allowed opacity-55' => $pendingHere,
                            'cursor-pointer' => ! $pendingHere,
                            'bg-sand text-[#5a3a22] hover:bg-sand-soft' => $featured && ! $pendingHere,
                            'bg-sand text-[#5a3a22]' => $featured && $pendingHere,
                            'bg-terracotta text-white hover:bg-earth' => ! $featured && ! $pendingHere,
                            'bg-terracotta text-white' => ! $featured && $pendingHere,
                        ])
                    >
                        @if ($pendingHere)
                            Solicitud enviada
                        @else
                            <span wire:loading.remove wire:target="requestPlan({{ $plan->id }})">Solicitar este pase</span>
                            <span wire:loading wire:target="requestPlan({{ $plan->id }})">Enviando…</span>
                        @endif
                    </button>

                    @if ($pendingHere)
                        <div @class([
                            'mt-2.5 text-center text-[12.5px] font-medium',
                            'text-sand' => $featured,
                            'text-earth' => ! $featured,
                        ])>
                            Ya tenés una solicitud pendiente para este pase
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- MIS SOLICITUDES --}}
        <div class="mb-5 flex items-center gap-3">
            <h2 class="text-[clamp(1.375rem,3vw,1.875rem)] font-semibold text-ink">Mis solicitudes</h2>
            <span class="text-sm text-ink-subtle">({{ $orders->count() }})</span>
        </div>

        <div class="overflow-hidden rounded-3xl border border-earth/12 bg-cream-card">
            @if ($orders->isEmpty())
                <div class="px-6 py-11 text-center text-[15px] text-ink-subtle">
                    Todavía no enviaste ninguna solicitud.
                </div>
            @else
                <div class="hidden grid-cols-[2fr_1fr_1fr_1fr_auto] gap-4 border-b border-earth/12 px-6 py-3.5 text-[12.5px] font-semibold tracking-[0.08em] text-ink-subtle uppercase md:grid">
                    <div>Pase</div>
                    <div>Fecha</div>
                    <div>Precio</div>
                    <div>Estado</div>
                    <div class="text-right">Acción</div>
                </div>

                @foreach ($orders as $order)
                    @php
                        $badge = match ($order->status) {
                            \App\Enums\MembershipOrderStatus::Approved => 'bg-olive/15 text-olive',
                            \App\Enums\MembershipOrderStatus::Rejected => 'bg-terracotta/12 text-terracotta',
                            \App\Enums\MembershipOrderStatus::Cancelled => 'bg-earth/10 text-ink-subtle',
                            default => 'bg-sand/35 text-[#8e6a2f]',
                        };
                    @endphp

                    <div @class([
                        'flex flex-col gap-2 px-6 py-4 md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:items-center md:gap-4',
                        'border-t border-earth/[0.08]' => ! $loop->first,
                    ])>
                        <div class="flex flex-col">
                            <span class="font-semibold text-ink">{{ $order->plan?->name ?? 'Pase' }}</span>
                            <span class="text-[13px] text-ink-subtle">
                                @if ($order->plan?->isUnlimited())
                                    Prácticas ilimitadas
                                @elseif ($credits = $order->plan?->credits())
                                    {{ $credits }} {{ Str::plural('práctica', $credits) }}
                                @endif
                            </span>
                        </div>

                        <div class="text-[14.5px] text-ink-soft">
                            <span class="text-ink-subtle md:hidden">Fecha · </span>{{ $order->created_at->format('d/m/Y') }}
                        </div>

                        <div class="text-[14.5px] text-ink-soft">
                            <span class="text-ink-subtle md:hidden">Precio · </span>{{ $order->price->format() }}
                        </div>

                        <div>
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">
                                {{ $order->status->label() }}
                            </span>
                        </div>

                        <div class="md:text-right">
                            @if ($order->isPending())
                                <button wire:click="cancelOrder({{ $order->id }})" wire:loading.attr="disabled"
                                        class="cursor-pointer rounded-full border border-terracotta/40 px-4 py-2 text-[13.5px] font-semibold text-terracotta transition hover:bg-terracotta hover:text-white">
                                    Cancelar
                                </button>
                            @else
                                <span class="text-[13px] text-ink-subtle/60" aria-hidden="true">—</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- TOAST --}}
    @if (session('status') || session('error'))
        @php $isError = (bool) session('error'); @endphp
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 6000)"
            x-cloak
            class="pointer-events-none fixed inset-x-0 bottom-6 z-100 flex justify-center px-5"
            role="status"
        >
            <div @class([
                'flex items-center gap-3 rounded-full px-5 py-3.5 shadow-[0_20px_50px_-20px_rgba(67,48,31,0.8)]',
                'bg-terracotta text-white' => $isError,
                'bg-olive text-olive-tint' => ! $isError,
            ])>
                <span class="flex size-6 flex-none items-center justify-center rounded-full bg-white/25 text-sm font-bold" aria-hidden="true">
                    {{ $isError ? '!' : '✓' }}
                </span>
                <span class="text-[15px]/snug font-medium">{{ session('error') ?? session('status') }}</span>
                <button type="button" @click="show = false"
                        class="pointer-events-auto ml-1 cursor-pointer text-lg opacity-70 transition hover:opacity-100"
                        aria-label="Cerrar">×</button>
            </div>
        </div>
    @endif
</div>
