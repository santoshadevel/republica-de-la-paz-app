<?php

namespace App\Filament\Resources\ScheduledSessions\Pages;

use App\Enums\ActivityType;
use App\Enums\SessionStatus;
use App\Filament\Resources\ScheduledSessions\ScheduledSessionResource;
use App\Models\Activity;
use App\Models\Practitioner;
use App\Models\Room;
use App\Services\Scheduling\SchedulingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListScheduledSessions extends ListRecords
{
    protected static string $resource = ScheduledSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->generateRecurringAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Bulk-load a weekly schedule: pick the activity, weekdays, time and date
     * range; the SchedulingService materialises every occurrence as its own
     * session (skipping conflicts and duplicates).
     */
    private function generateRecurringAction(): Action
    {
        return Action::make('generateRecurring')
            ->label('Programar recurrentes')
            ->icon('heroicon-o-arrow-path')
            ->modalHeading('Programar sesiones recurrentes')
            ->modalDescription('Genera una sesión por cada día seleccionado dentro del rango de fechas.')
            ->modalSubmitActionLabel('Generar')
            ->schema([
                Select::make('activity_id')
                    ->label('Actividad')
                    ->options(fn () => Activity::query()
                        ->where('type', ActivityType::GroupClass->value)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('practitioner_id')
                    ->label('Facilitador')
                    ->options(fn () => Practitioner::query()
                        ->get()
                        ->mapWithKeys(fn (Practitioner $p) => [$p->id => $p->fullName()]))
                    ->searchable(),
                Select::make('room_id')
                    ->label('Sala')
                    ->options(fn () => Room::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                CheckboxList::make('weekdays')
                    ->label('Días de la semana')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        7 => 'Domingo',
                    ])
                    ->columns(4)
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Hora de inicio')
                    ->seconds(false)
                    ->native(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Hora de fin')
                    ->seconds(false)
                    ->native(false)
                    ->required()
                    ->after('start_time'),
                DatePicker::make('from')
                    ->label('Desde')
                    ->native(false)
                    ->required(),
                DatePicker::make('to')
                    ->label('Hasta')
                    ->native(false)
                    ->required()
                    ->afterOrEqual('from'),
                TextInput::make('capacity')
                    ->label('Cupo')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::Scheduled->value)
                    ->required(),
                Toggle::make('skip_past')
                    ->label('Omitir fechas pasadas')
                    ->default(true),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $result = app(SchedulingService::class)->generateRecurringSessions($data);

                $body = "Sesiones creadas: {$result['created']}. Omitidas: {$result['skipped']}.";
                if ($result['conflicts'] !== []) {
                    $body .= ' '.count($result['conflicts']).' por conflicto de sala/profesional o disponibilidad.';
                }

                $notification = Notification::make()
                    ->title('Programación de sesiones')
                    ->body($body);

                ($result['created'] > 0 ? $notification->success() : $notification->warning())->send();
            });
    }
}
