<?php

namespace App\Services\Scheduling;

use App\Enums\SessionStatus;
use App\Models\Practitioner;
use App\Models\ScheduledSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates the agenda of group sessions. Framework-agnostic on purpose:
 * Filament calls it today; the REST API and the coordination bot (MCP) will call
 * the same methods tomorrow without a rewrite.
 *
 * Design note: this materialises a weekly intent into concrete ScheduledSession
 * rows (the "template → occurrences" approach). No recurrence rule is stored, so
 * every occurrence stays an independent, addressable, bookable row — which is
 * exactly what a scheduling bot operates on. See docs/REQUISITOS.md (plantilla
 * vs ocurrencia).
 */
class SchedulingService
{
    /**
     * Materialise concrete group sessions from a weekly recurrence intent.
     *
     * Occurrences that would collide with an existing session (same room or same
     * practitioner at an overlapping time) or that already exist are skipped and
     * reported, never created.
     *
     * @param  array{
     *   activity_id:int, practitioner_id?:int|null, room_id?:int|null,
     *   weekdays:array<int|string>, start_time:string, end_time:string,
     *   from:string, to:string, capacity:int|string, status?:string,
     *   notes?:string|null, skip_past?:bool
     * }  $data
     * @return array{created:int, skipped:int, conflicts:array<int,array{starts_at:string, reason:string}>}
     */
    public function generateRecurringSessions(array $data): array
    {
        if (Carbon::parse($data['start_time'])->gte(Carbon::parse($data['end_time']))) {
            throw new InvalidArgumentException('La hora de fin debe ser posterior a la de inicio.');
        }

        $weekdays = array_map('intval', $data['weekdays']);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->startOfDay();
        $skipPast = $data['skip_past'] ?? true;
        $now = Carbon::now();

        // Only enforce availability when the practitioner has a schedule configured.
        $practitioner = filled($data['practitioner_id'] ?? null)
            ? Practitioner::with(['availabilities', 'availabilityExceptions'])->find($data['practitioner_id'])
            : null;

        $created = 0;
        $skipped = 0;
        $conflicts = [];

        DB::transaction(function () use (&$created, &$skipped, &$conflicts, $data, $from, $to, $weekdays, $skipPast, $now, $practitioner) {
            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if (! in_array($date->dayOfWeekIso, $weekdays, true)) {
                    continue;
                }

                $startsAt = $this->combineDateAndTime($date, $data['start_time']);
                $endsAt = $this->combineDateAndTime($date, $data['end_time']);

                if ($skipPast && $startsAt->isBefore($now)) {
                    $skipped++;

                    continue;
                }

                if ($this->exactOccurrenceExists($data['activity_id'], $data['room_id'] ?? null, $startsAt)) {
                    $skipped++;

                    continue;
                }

                $reason = $this->findConflict($data['room_id'] ?? null, $data['practitioner_id'] ?? null, $startsAt, $endsAt);
                if ($reason !== null) {
                    $skipped++;
                    $conflicts[] = ['starts_at' => $startsAt->format('d/m/Y H:i'), 'reason' => $reason];

                    continue;
                }

                if ($practitioner !== null && ! $practitioner->isAvailableAt($startsAt, $endsAt)) {
                    $skipped++;
                    $conflicts[] = ['starts_at' => $startsAt->format('d/m/Y H:i'), 'reason' => 'El profesional no está disponible en ese horario.'];

                    continue;
                }

                ScheduledSession::schedule([
                    'activity_id' => $data['activity_id'],
                    'practitioner_id' => $data['practitioner_id'] ?? null,
                    'room_id' => $data['room_id'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'capacity' => (int) $data['capacity'],
                    'status' => $data['status'] ?? SessionStatus::Scheduled->value,
                    'notes' => $data['notes'] ?? null,
                ]);
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped, 'conflicts' => $conflicts];
    }

    /**
     * Reason the given slot cannot be booked (room or practitioner already busy),
     * or null if it is free. Cancelled sessions never block. Reusable by the
     * booking/reschedule flows and the coordination bot.
     */
    public function findConflict(?int $roomId, ?int $practitionerId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): ?string
    {
        if ($roomId !== null && $this->overlapping($startsAt, $endsAt, $ignoreId)->where('room_id', $roomId)->exists()) {
            return 'La sala ya está ocupada en ese horario.';
        }

        if ($practitionerId !== null && $this->overlapping($startsAt, $endsAt, $ignoreId)->where('practitioner_id', $practitionerId)->exists()) {
            return 'El profesional ya tiene una sesión en ese horario.';
        }

        return null;
    }

    /** Sessions (not cancelled) whose time range overlaps [startsAt, endsAt). */
    private function overlapping(Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): Builder
    {
        return ScheduledSession::query()
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId));
    }

    /** True when an identical occurrence already exists (idempotent re-runs). */
    private function exactOccurrenceExists(int $activityId, ?int $roomId, Carbon $startsAt): bool
    {
        return ScheduledSession::query()
            ->where('activity_id', $activityId)
            ->where('room_id', $roomId)
            ->where('starts_at', $startsAt)
            ->exists();
    }

    /** Combine a calendar day with a "HH:MM" time into a concrete datetime. */
    private function combineDateAndTime(Carbon $date, string $time): Carbon
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return $date->copy()->setTime((int) $hour, (int) $minute);
    }
}
