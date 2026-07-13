<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Ingresar</h1>
    <p class="mt-1 text-sm text-stone-500">Accedé a tu espacio de la comunidad.</p>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
            <input type="email" id="email" wire:model="email" autocomplete="email"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">Contraseña</label>
            <input type="password" id="password" wire:model="password" autocomplete="current-password"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-stone-500 focus:ring-stone-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" wire:model="remember" class="rounded border-stone-300 text-stone-900">
            Recordarme
        </label>

        <button type="submit"
                class="w-full rounded-lg bg-stone-900 px-4 py-2.5 font-medium text-white hover:bg-stone-700">
            <span wire:loading.remove wire:target="login">Ingresar</span>
            <span wire:loading wire:target="login">Ingresando…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        ¿No tenés cuenta?
        <a href="{{ route('register') }}" class="font-medium text-stone-900 hover:underline">Registrate</a>
    </p>
</div>
