<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Pases y membresías</h1>
        <p class="mt-1 text-sm text-stone-500">Elegí tu pase. Confirmamos el pago y lo activamos.</p>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($plans as $plan)
            <div class="flex flex-col rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $plan->name }}</h2>
                    @if ($plan->description)
                        <p class="mt-1 text-sm text-stone-500">{{ $plan->description }}</p>
                    @endif
                    <ul class="mt-3 space-y-1 text-sm text-stone-600">
                        <li>· {{ $plan->isUnlimited() ? 'Prácticas ilimitadas' : $plan->credits().' prácticas' }}</li>
                        @if ($plan->validityDays())
                            <li>· Vigencia {{ $plan->validityDays() }} días</li>
                        @endif
                    </ul>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xl font-semibold text-stone-900">{{ $plan->price }}</span>
                    <button wire:click="requestPlan({{ $plan->id }})" wire:loading.attr="disabled"
                            class="rounded-full bg-stone-900 px-5 py-2 text-sm font-medium text-white hover:bg-stone-700">
                        Solicitar
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if ($orders->isNotEmpty())
        <section>
            <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-stone-500">Mis solicitudes</h2>
            <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white">
                @foreach ($orders as $order)
                    <li class="flex items-center justify-between px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-stone-800">{{ $order->plan?->name ?? 'Pase' }}</p>
                            <p class="text-stone-400">{{ $order->created_at->format('d/m/Y') }} · {{ $order->price }}</p>
                        </div>
                        @php
                            $badge = match ($order->status) {
                                \App\Enums\MembershipOrderStatus::Approved => 'bg-emerald-50 text-emerald-700',
                                \App\Enums\MembershipOrderStatus::Rejected => 'bg-red-50 text-red-600',
                                default => 'bg-amber-50 text-amber-700',
                            };
                        @endphp
                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
