<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Crear mi cuenta</h1>
    <p class="mt-1 text-sm text-stone-500">Unite a la comunidad y empezá a reservar.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-stone-700">Nombre y apellido</label>
            <input type="text" id="name" wire:model="name" autocomplete="name"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
            <input type="email" id="email" wire:model="email" autocomplete="email"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">Contraseña</label>
            <input type="password" id="password" wire:model="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Repetir contraseña</label>
            <input type="password" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-stone-900 px-4 py-2.5 font-medium text-white hover:bg-stone-700">
            <span wire:loading.remove wire:target="register">Crear cuenta</span>
            <span wire:loading wire:target="register">Creando…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        ¿Ya tenés cuenta?
        <a href="{{ route('login') }}" class="font-medium text-stone-900 hover:underline">Ingresá</a>
    </p>
</div>
