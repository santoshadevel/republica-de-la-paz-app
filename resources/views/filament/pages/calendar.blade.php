<x-filament-panels::page>
    {{-- Legend --}}
    <div class="fi-ta-header-toolbar flex flex-wrap items-center gap-4 text-sm">
        <span class="flex items-center gap-2">
            <span class="inline-block h-3 w-3 rounded-full" style="background:#0ea5e9"></span>
            Sesiones grupales
        </span>
        <span class="flex items-center gap-2">
            <span class="inline-block h-3 w-3 rounded-full" style="background:#8b5cf6"></span>
            Acompañamientos
        </span>
        <span class="flex items-center gap-2">
            <span class="inline-block h-3 w-3 rounded-full" style="background:#f59e0b"></span>
            Eventos
        </span>
    </div>

    <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div wire:ignore>
            <div x-ref="calendar" class="santosha-calendar"></div>
        </div>
    </div>

    @assets
        <script src="{{ asset('js/vendor/fullcalendar/index.global.min.js') }}"></script>
        <script src="{{ asset('js/vendor/fullcalendar/locale-es.global.min.js') }}"></script>
        <style>
            /* Readable in Filament's dark theme (FullCalendar defaults to light). */
            .dark .santosha-calendar {
                --fc-border-color: rgba(255, 255, 255, 0.1);
                --fc-page-bg-color: transparent;
                --fc-neutral-bg-color: rgba(255, 255, 255, 0.03);
                --fc-today-bg-color: rgba(245, 158, 11, 0.08);
                color: #e5e7eb;
            }
            .dark .santosha-calendar .fc-col-header-cell-cushion,
            .dark .santosha-calendar .fc-daygrid-day-number,
            .dark .santosha-calendar .fc-timegrid-slot-label-cushion,
            .dark .santosha-calendar .fc-list-day-text,
            .dark .santosha-calendar .fc-toolbar-title {
                color: #e5e7eb;
            }
            .santosha-calendar .fc-event {
                cursor: pointer;
                padding: 1px 3px;
                font-size: 0.75rem;
            }
            .santosha-calendar a.fc-event:hover {
                filter: brightness(1.08);
            }
            @media (max-width: 767px) {
                .santosha-calendar .fc-header-toolbar.fc-toolbar {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 0.5rem;
                    margin-bottom: 0.75rem;
                }
                .santosha-calendar .fc-toolbar-title { font-size: 1rem; }
                .santosha-calendar .fc-footer-toolbar.fc-toolbar { margin-top: 0.75rem; }
                .santosha-calendar .fc-button { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
                .santosha-calendar .fc-toolbar-chunk { display: flex; justify-content: center; }
            }
        </style>
    @endassets

    @script
        <script>
            const el = $wire.$el.querySelector('[x-ref="calendar"]');

            const desktopHeader = {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            };
            const mobileHeader = { left: 'prev,next', center: 'title', right: 'today' };
            const mobileFooter = { center: 'dayGridMonth,listWeek' };
            const mq = window.matchMedia('(max-width: 767px)');

            const calendar = new FullCalendar.Calendar(el, {
                initialView: mq.matches ? 'listWeek' : 'timeGridWeek',
                locale: 'es',
                firstDay: 1,
                nowIndicator: true,
                slotMinTime: '06:00:00',
                slotMaxTime: '23:00:00',
                expandRows: true,
                height: 'auto',
                allDaySlot: false,
                headerToolbar: mq.matches ? mobileHeader : desktopHeader,
                footerToolbar: mq.matches ? mobileFooter : false,
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Lista',
                },
                events: (info, success, failure) => {
                    $wire.fetchEvents(info.startStr, info.endStr).then(success).catch(failure);
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    const sessionId = info.event.extendedProps.sessionId;
                    if (sessionId) {
                        // Group session → manage its roster / reserve a student.
                        $wire.mountAction('manageSession', { session: sessionId });
                    } else if (info.event.url) {
                        // Appointments / events → open their edit screen.
                        window.location.assign(info.event.url);
                    }
                },
                eventDidMount: (info) => {
                    const p = info.event.extendedProps;
                    const parts = [p.type, p.practitioner, p.room, p.student, p.occupancy, p.location].filter(Boolean);
                    if (parts.length) {
                        info.el.setAttribute('title', parts.join(' · '));
                    }
                },
            });

            // Swap toolbar + view when crossing the mobile breakpoint.
            let isMobile = mq.matches;
            const applyResponsive = () => {
                if (mq.matches === isMobile) return;
                isMobile = mq.matches;
                if (isMobile) {
                    calendar.setOption('headerToolbar', mobileHeader);
                    calendar.setOption('footerToolbar', mobileFooter);
                    if (calendar.view.type === 'timeGridWeek' || calendar.view.type === 'timeGridDay') {
                        calendar.changeView('listWeek');
                    }
                } else {
                    calendar.setOption('headerToolbar', desktopHeader);
                    calendar.setOption('footerToolbar', false);
                }
            };
            mq.addEventListener('change', applyResponsive);

            calendar.render();

            // Refresh occupancy after a booking/cancellation from the manage modal.
            $wire.on('calendar-refresh', () => calendar.refetchEvents());
        </script>
    @endscript

    <x-filament-actions::modals />
</x-filament-panels::page>
