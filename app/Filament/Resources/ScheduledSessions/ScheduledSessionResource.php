<?php

namespace App\Filament\Resources\ScheduledSessions;

use App\Filament\Resources\ScheduledSessions\Pages\CreateScheduledSession;
use App\Filament\Resources\ScheduledSessions\Pages\EditScheduledSession;
use App\Filament\Resources\ScheduledSessions\Pages\ListScheduledSessions;
use App\Filament\Resources\ScheduledSessions\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\ScheduledSessions\Schemas\ScheduledSessionForm;
use App\Filament\Resources\ScheduledSessions\Tables\ScheduledSessionsTable;
use App\Models\ScheduledSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScheduledSessionResource extends Resource
{
    protected static ?string $model = ScheduledSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Agenda';

    protected static ?string $navigationLabel = 'Sesiones grupales';

    protected static ?string $modelLabel = 'Sesión grupal';

    protected static ?string $pluralModelLabel = 'Sesiones grupales';

    public static function form(Schema $schema): Schema
    {
        return ScheduledSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledSessions::route('/'),
            'create' => CreateScheduledSession::route('/create'),
            'edit' => EditScheduledSession::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
