<div>
    <x-auth-card
        tone="earth"
        eyebrow="Portal del ciudadano"
        heading="Bienvenida/o de nuevo a la República"
        lead="Ingresá para reservar tus prácticas, ver tu pase y sumarte a los encuentros de la comunidad."
    >
        <x-slot:aside>
            <div class="flex gap-6">
                <div>
                    <div class="text-xl font-semibold text-sand">Reservá</div>
                    <div class="text-[13.5px] text-sand-dim">tus prácticas</div>
                </div>
                <div>
                    <div class="text-xl font-semibold text-sand">Gestioná</div>
                    <div class="text-[13.5px] text-sand-dim">tu pase</div>
                </div>
            </div>
        </x-slot:aside>

        <h2 class="mb-1.5 text-2xl font-semibold text-ink">Ingresar</h2>
        <p class="mb-6 text-[15px] text-ink-soft">
            ¿Todavía no sos ciudadano?
            <a href="{{ route('register') }}" class="font-semibold text-terracotta hover:text-earth">Registrate</a>
        </p>

        <form wire:submit="login" class="flex flex-col gap-4.5">
            <x-form-field label="Email" name="email" type="email" autocomplete="email" wire:model="email" />
            <x-form-field label="Contraseña" name="password" type="password" autocomplete="current-password" wire:model="password" />

            <label class="flex cursor-pointer items-center gap-2.5 text-[14.5px] text-ink-muted select-none">
                <input type="checkbox" wire:model="remember" class="size-4.5 rounded accent-terracotta">
                Recordarme
            </label>

            <button type="submit"
                    class="cursor-pointer rounded-full bg-terracotta py-4 text-base font-semibold text-white transition hover:bg-earth">
                <span wire:loading.remove wire:target="login">Ingresar</span>
                <span wire:loading wire:target="login">Ingresando…</span>
            </button>
        </form>

        <p class="mt-5.5 text-center text-[13px] text-ink-subtle">
            Zona privada de la comunidad {{ config('app.name') }}
        </p>
    </x-auth-card>
</div>
