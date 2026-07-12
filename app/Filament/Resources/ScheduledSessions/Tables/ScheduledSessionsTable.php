<?php

namespace App\Filament\Resources\ScheduledSessions\Tables;

use App\Enums\SessionStatus;
use App\Models\ScheduledSession;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ScheduledSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('activity.name')
                    ->label('Actividad')
                    ->searchable(),
                TextColumn::make('practitioner.first_name')
                    ->label('Facilitador')
                    ->formatStateUsing(fn ($record) => $record->practitioner?->fullName())
                    ->placeholder('—'),
                TextColumn::make('room.name')
                    ->label('Sala')
                    ->placeholder('—'),
                TextColumn::make('occupancy')
                    ->label('Cupos')
                    ->state(fn (ScheduledSession $record) => "{$record->seatsTaken()} / {$record->capacity}"),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SessionStatus::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
