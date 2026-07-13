<?php

namespace App\Filament\Resources\MembershipOrders;

use App\Actions\Memberships\ApproveMembershipOrder;
use App\Actions\Memberships\RejectMembershipOrder;
use App\Filament\Resources\MembershipOrders\Pages\ListMembershipOrders;
use App\Models\MembershipOrder;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MembershipOrderResource extends Resource
{
    protected static ?string $model = MembershipOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Solicitudes de pase';

    protected static ?string $modelLabel = 'Solicitud de pase';

    protected static ?string $pluralModelLabel = 'Solicitudes de pase';

    public static function getNavigationBadge(): ?string
    {
        $pending = MembershipOrder::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Alumno')
                    ->formatStateUsing(fn (MembershipOrder $record) => $record->student?->fullName())
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Pase'),
                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => (string) $state),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Solicitada')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (MembershipOrder $record) => $record->isPending())
                    ->schema([
                        Select::make('payment_method_id')
                            ->label('Método de pago')
                            ->options(fn () => PaymentMethod::query()->where('is_active', true)->pluck('name', 'id'))
                            ->helperText('Opcional: registra el ingreso en contabilidad.'),
                    ])
                    ->action(function (array $data, MembershipOrder $record): void {
                        $method = filled($data['payment_method_id'] ?? null)
                            ? PaymentMethod::find($data['payment_method_id'])
                            : null;

                        try {
                            app(ApproveMembershipOrder::class)->execute($record, Auth::user(), $method);
                            Notification::make()->success()->title('Pase activado')->send();
                        } catch (Throwable $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MembershipOrder $record) => $record->isPending())
                    ->action(function (MembershipOrder $record): void {
                        try {
                            app(RejectMembershipOrder::class)->execute($record, Auth::user());
                            Notification::make()->success()->title('Solicitud rechazada')->send();
                        } catch (Throwable $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembershipOrders::route('/'),
        ];
    }
}
