<x-layouts.app>
    <section class="py-12 text-center">
        <p class="text-sm font-medium uppercase tracking-widest text-amber-600">Bienestar · Comunidad</p>
        <h1 class="mt-3 text-4xl font-semibold tracking-tight text-stone-900 sm:text-5xl">
            {{ config('app.name') }}
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-stone-600">
            Reservá tus prácticas, gestioná tu pase y seguí tu camino — todo desde un solo lugar.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('register') }}"
               class="rounded-full bg-stone-900 px-6 py-3 font-medium text-white hover:bg-stone-700">
                Crear mi cuenta
            </a>
            <a href="{{ route('login') }}"
               class="rounded-full border border-stone-300 px-6 py-3 font-medium text-stone-700 hover:border-stone-400">
                Ya tengo cuenta
            </a>
        </div>
    </section>
</x-layouts.app>
