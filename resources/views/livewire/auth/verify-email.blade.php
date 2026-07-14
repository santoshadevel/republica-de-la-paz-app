<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Verificá tu email</h1>
    <p class="mt-1 text-sm text-stone-500">
        Te enviamos un enlace a <span class="font-medium text-stone-700">{{ $email }}</span>.
        Abrilo para activar tu cuenta y empezar a reservar.
    </p>

    @if (session('status'))
        <div class="mt-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <button type="button" wire:click="resend" wire:loading.attr="disabled"
            class="mt-6 w-full rounded-lg bg-stone-900 px-4 py-2.5 font-medium text-white hover:bg-stone-700">
        <span wire:loading.remove wire:target="resend">Reenviar el enlace</span>
        <span wire:loading wire:target="resend">Enviando…</span>
    </button>

    <p class="mt-6 text-center text-sm text-stone-500">
        ¿Te equivocaste de dirección?
        <button type="button" onclick="document.getElementById('logout-form').submit()"
                class="font-medium text-stone-900 hover:underline">Salir y volver a empezar</button>
    </p>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
</div>
