<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Riwayat Pembayaran';

    public static function getModelLabel(): string
    {
        return 'Pembayaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Pembayaran';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\TextInput::make('payment_type')
                    ->required(),
                Forms\Components\TextInput::make('payment_gateway')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_gateway_reference_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_gateway_response')
                    ->required(),
                Forms\Components\TextInput::make('payment_va_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_va_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_ewallet')
                    ->maxLength(255),
                Forms\Components\TextInput::make('gross_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payment_proof')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('payment_date'),
                Forms\Components\DateTimePicker::make('expired_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_gateway_reference_id')
                    ->label('Nomer Pesanan')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('order_id')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status Pembayaran'),
                // Tables\Columns\TextColumn::make('payment_type'),
                // Tables\Columns\TextColumn::make('payment_gateway')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('payment_va_name')
                    ->label('Nama Bank')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_va_number')
                    ->label('Nomer Bank')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('payment_ewallet')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Total Pembayaran')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('payment_proof')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('payment_date')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('expired_at')
                //     ->dateTime()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                    // ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            // 'create' => Pages\CreatePayment::route('/create'),
            // 'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
