<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Actions\Memberships\AdjustMembershipCredits;
use App\Actions\Memberships\SellMembership;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\StudentMembership;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Manages a student's memberships from their profile: sell a pass, see the
 * balance/validity, and manually adjust credits. Business logic lives in the
 * Actions (reusable by the future API); this only orchestrates.
 */
class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $title = 'Membresías y saldo';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—'),
                TextColumn::make('credits_remaining')
                    ->label('Saldo')
                    ->state(fn (StudentMembership $record) => $record->is_unlimited
                        ? 'Ilimitado'
                        : $record->creditsRemaining()),
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->date(),
                TextColumn::make('ends_at')
                    ->label('Vence')
                    ->date(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('price_paid')
                    ->label('Precio')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('sell')
                    ->label('Vender membresía')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('membership_plan_id')
                            ->label('Plan')
                            ->options(fn () => MembershipPlan::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('starts_at')
                            ->label('Inicio de vigencia')
                            ->native(false)
                            ->default(now()),
                        Select::make('payment_method_id')
                            ->label('Método de pago')
                            ->options(fn () => PaymentMethod::query()->where('is_active', true)->pluck('name', 'id'))
                            ->helperText('Registra el ingreso en contabilidad.'),
                    ])
                    ->action(function (array $data): void {
                        $plan = MembershipPlan::findOrFail($data['membership_plan_id']);
                        $paymentMethod = filled($data['payment_method_id'] ?? null)
                            ? PaymentMethod::find($data['payment_method_id'])
                            : null;

                        app(SellMembership::class)->execute(
                            $this->getOwnerRecord(),
                            $plan,
                            filled($data['starts_at'] ?? null) ? Carbon::parse($data['starts_at']) : null,
                            $paymentMethod,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('adjust')
                    ->label('Ajustar créditos')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (StudentMembership $record) => ! $record->is_unlimited)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Cantidad')
                            ->helperText('Positivo agrega, negativo descuenta (ej. 2 o -1).')
                            ->numeric()
                            ->required(),
                        TextInput::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, StudentMembership $record): void {
                        app(AdjustMembershipCredits::class)->execute(
                            $record,
                            (int) $data['amount'],
                            $data['reason'],
                            Auth::user(),
                        );
                    }),
            ]);
    }
}
