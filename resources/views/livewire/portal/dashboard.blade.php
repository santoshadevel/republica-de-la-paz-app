<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">
            Hola{{ $student ? ', '.$student->first_name : '' }} 👋
        </h1>
        <p class="mt-1 text-sm text-stone-500">Este es tu espacio en la comunidad.</p>
    </div>

    {{-- Membership card --}}
    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-medium uppercase tracking-wide text-stone-500">Mi pase</h2>
        @if ($membership)
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-lg font-semibold text-stone-900">{{ $membership->plan?->name ?? 'Pase' }}</p>
                    <p class="text-sm text-stone-500">
                        Vence el {{ $membership->ends_at?->format('d/m/Y') }} ·
                        @if ($membership->isCurrentlyActive())
                            <span class="text-emerald-600">vigente</span>
                        @else
                            <span class="text-red-600">vencido</span>
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-semibold text-stone-900">
                        {{ $membership->is_unlimited ? '∞' : $membership->creditsRemaining() }}
                    </p>
                    <p class="text-xs uppercase tracking-wide text-stone-400">prácticas</p>
                </div>
            </div>
        @else
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-stone-600">Todavía no tenés un pase activo.</p>
                <a href="{{ route('portal.plans') }}"
                   class="rounded-full bg-stone-900 px-4 py-1.5 text-xs font-medium text-white hover:bg-stone-700">
                    Comprar pase
                </a>
            </div>
        @endif
    </section>

    {{-- Upcoming agenda --}}
    <section>
        <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-stone-500">Mi agenda</h2>
        @forelse ($agenda['upcoming'] as $entry)
            <div class="mb-2 flex items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-3">
                <div>
                    <p class="font-medium text-stone-900">{{ $entry['title'] }}</p>
                    <p class="text-sm text-stone-500">{{ $entry['type'] }} · {{ $entry['status'] }}</p>
                </div>
                <div class="text-right text-sm text-stone-600">
                    <p class="font-medium">{{ $entry['starts_at']?->format('d/m') }}</p>
                    <p>{{ $entry['starts_at']?->format('H:i') }}</p>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-stone-300 px-4 py-8 text-center text-sm text-stone-500">
                No tenés nada agendado. Pronto vas a poder reservar desde acá.
            </div>
        @endforelse
    </section>

    {{-- History --}}
    @if (! empty($agenda['past']))
        <section>
            <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-stone-500">Historial</h2>
            <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white">
                @foreach (array_slice($agenda['past'], 0, 5) as $entry)
                    <li class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-stone-700">{{ $entry['title'] }}</span>
                        <span class="text-stone-400">{{ $entry['starts_at']?->format('d/m/Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
