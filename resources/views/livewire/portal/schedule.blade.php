<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Reservar prácticas</h1>
            <p class="mt-1 text-sm text-stone-500">Tocá una clase para reservar o cancelar.</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-stone-500">
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#0ea5e9"></span>Disponible</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#059669"></span>Reservada</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#a8a29e"></span>Completa</span>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if (! $membership)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span>Necesitás un pase vigente para reservar.</span>
            <a href="{{ route('portal.plans') }}" class="font-medium underline">Ver pases</a>
        </div>
    @elseif (! $canBook)
        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Tu pase no tiene prácticas disponibles o está vencido. <a href="{{ route('portal.plans') }}" class="font-medium underline">Renovar</a>
        </div>
    @endif

    {{-- Calendar --}}
    <div class="rounded-2xl border border-stone-200 bg-white p-3 shadow-sm sm:p-4">
        <div wire:ignore>
            <div id="portal-cal" class="portal-calendar"></div>
        </div>
    </div>

    {{-- Session modal --}}
    <div x-data="{ open: false, s: {} }"
         @portal-session.window="s = $event.detail; open = true"
         x-cloak>
        <div x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" style="display:none;">
            <div class="absolute inset-0 bg-stone-900/40" @click="open = false"></div>
            <div class="relative w-full max-w-sm rounded-t-2xl bg-white p-6 shadow-xl sm:rounded-2xl">
                <h3 class="text-lg font-semibold text-stone-900" x-text="s.activity"></h3>
                <p class="mt-1 text-sm text-stone-500" x-text="s.when"></p>
                <p class="text-sm text-stone-500">
                    <span x-show="s.practitioner" x-text="s.practitioner"></span>
                    <span x-show="s.room" x-text="' · ' + s.room"></span>
                </p>
                <p class="mt-1 text-sm" :class="s.free > 0 ? 'text-stone-400' : 'text-red-500'">
                    <span x-text="s.free"></span> lugares
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button @click="open = false" class="rounded-full px-4 py-2 text-sm text-stone-500 hover:text-stone-800">Cerrar</button>

                    <template x-if="s.booked">
                        <button @click="$wire.cancel(s.bookingId); open = false"
                                class="rounded-full border border-stone-300 px-5 py-2 text-sm font-medium text-red-600 hover:border-red-300">
                            Cancelar reserva
                        </button>
                    </template>
                    <template x-if="!s.booked && s.free > 0 && s.canBook">
                        <button @click="$wire.book(s.sessionId); open = false"
                                class="rounded-full bg-stone-900 px-5 py-2 text-sm font-medium text-white hover:bg-stone-700">
                            Reservar
                        </button>
                    </template>
                    <template x-if="!s.booked && s.free <= 0">
                        <span class="rounded-full bg-stone-100 px-5 py-2 text-sm text-stone-400">Completa</span>
                    </template>
                    <template x-if="!s.booked && s.free > 0 && !s.canBook">
                        <a href="{{ route('portal.plans') }}" class="rounded-full bg-amber-500 px-5 py-2 text-sm font-medium text-white hover:bg-amber-600">Necesitás un pase</a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @assets
        <script src="{{ asset('js/vendor/fullcalendar/index.global.min.js') }}"></script>
        <script src="{{ asset('js/vendor/fullcalendar/locale-es.global.min.js') }}"></script>
        <style>
            [x-cloak] { display: none !important; }
            .portal-calendar .fc-event { cursor: pointer; }
            .portal-calendar .fc-toolbar-title { font-size: 1.05rem; }
        </style>
    @endassets

    @script
        <script>
            const calendar = new FullCalendar.Calendar($wire.$el.querySelector('#portal-cal'), {
                initialView: 'timeGridWeek',
                locale: 'es',
                firstDay: 1,
                nowIndicator: true,
                allDaySlot: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '23:00:00',
                height: 'auto',
                expandRows: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek',
                },
                buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', list: 'Lista' },
                events: (info, success, failure) => {
                    $wire.fetchEvents(info.startStr, info.endStr).then(success).catch(failure);
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    window.dispatchEvent(new CustomEvent('portal-session', { detail: info.event.extendedProps }));
                },
            });

            calendar.render();
            $wire.on('calendar-refresh', () => calendar.refetchEvents());
        </script>
    @endscript
</div>
