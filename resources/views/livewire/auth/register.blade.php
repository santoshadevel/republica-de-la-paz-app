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

        <h2 class="mb-1.5 text-2xl font-semibold text-ink">Crear cuenta</h2>
        <p class="mb-6 text-[15px] text-ink-soft">
            ¿Ya sos ciudadano?
            <a href="{{ route('login') }}" class="font-semibold text-terracotta hover:text-earth">Ingresá</a>
        </p>

        <form wire:submit="register" class="flex flex-col gap-4.5">
            <x-form-field label="Nombre y apellido" name="name" autocomplete="name" wire:model="name" />
            <x-form-field label="Email" name="email" type="email" autocomplete="email" wire:model="email" />
            <x-form-field label="Contraseña" name="password" type="password" autocomplete="new-password" wire:model="password" />
            <x-form-field label="Confirmar contraseña" name="password_confirmation" type="password"
                          autocomplete="new-password" wire:model="password_confirmation" />

            <button type="submit"
                    class="cursor-pointer rounded-full bg-terracotta py-4 text-base font-semibold text-white transition hover:bg-earth">
                <span wire:loading.remove wire:target="register">Crear mi cuenta</span>
                <span wire:loading wire:target="register">Creando…</span>
            </button>
        </form>
    </x-auth-card>
</div>
