<div>
    <x-auth-card
        tone="olive"
        eyebrow="Sumate a la comunidad"
        heading="Hacete ciudadano de la República"
        lead="Creá tu cuenta para reservar prácticas, solicitar tu pase y participar de los encuentros."
    >
        <x-slot:aside>
            <ul class="flex flex-col gap-2.5">
                @foreach (['Tu primera práctica es gratuita', 'Reservá y cancelá online', 'Comunidad cálida y cercana'] as $perk)
                    <li class="flex gap-2.5 text-[15px] text-olive-soft">
                        <span class="font-bold text-sand" aria-hidden="true">✓</span>{{ $perk }}
                    </li>
                @endforeach
            </ul>
        </x-slot:aside>

        <div class="flex min-h-105 flex-col justify-center gap-4.5 py-2 text-center">
            <div class="mx-auto flex size-19 items-center justify-center rounded-full bg-olive/15 text-3xl text-olive" aria-hidden="true">✉</div>

            <h2 class="text-2xl font-semibold text-ink">Verificá tu email</h2>

            <p class="mx-auto max-w-90 text-base/relaxed text-ink-soft text-pretty">
                Te enviamos un enlace de verificación a <strong class="font-semibold text-earth">{{ $email }}</strong>.
                Abrilo para activar tu cuenta y entrar al portal.
            </p>

            @if (session('status'))
                <div class="mx-auto max-w-95 rounded-2xl border border-olive/25 bg-olive/10 px-4.5 py-3.5 text-sm/relaxed text-olive">
                    {{ session('status') }}
                </div>
            @elseif (session('error'))
                <div class="mx-auto max-w-95 rounded-2xl border border-terracotta/30 bg-terracotta/10 px-4.5 py-3.5 text-sm/relaxed text-earth">
                    {{ session('error') }}
                </div>
            @else
                <div class="mx-auto max-w-95 rounded-2xl border border-terracotta/20 bg-terracotta/8 px-4.5 py-3.5 text-sm/relaxed text-earth">
                    Hasta que verifiques tu email no vas a poder ingresar al portal.
                </div>
            @endif

            <div class="mt-1 flex flex-col items-center gap-3">
                <button type="button" wire:click="resend" wire:loading.attr="disabled"
                        class="cursor-pointer rounded-full bg-terracotta px-7 py-3.5 text-[15px] font-semibold text-white transition hover:bg-earth disabled:opacity-60">
                    <span wire:loading.remove wire:target="resend">Reenviar el enlace</span>
                    <span wire:loading wire:target="resend">Enviando…</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer text-sm font-semibold text-terracotta hover:text-earth">
                        Usar otro email
                    </button>
                </form>
            </div>
        </div>
    </x-auth-card>
</div>
