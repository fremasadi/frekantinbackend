<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerEarningsResource\Pages;
use App\Models\SellerEarning;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SellerEarningsResource extends Resource
{
    protected static ?string $model = SellerEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pendapatan Penjual';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seller_id')
                    ->label('Seller')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->disabledOn('edit')
                    ->required(),

                Forms\Components\DatePicker::make('month')
                    ->label('Bulan')
                    ->displayFormat('F Y')
                    ->required(),

                Forms\Components\TextInput::make('total_income')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Sudah Dibayar',
                    ])
                    ->required(),

                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Tanggal Dibayar')
                    ->visible(fn ($get) => $get('status') === 'paid'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Seller')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Tanggal')
                    ->date('F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_income')
                    ->label('Total Pendapatan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar';
                    }),
                

                // Tables\Columns\TextColumn::make('paid_at')
                //     ->label('Tanggal Dibayar')
                //     ->dateTime()
                //     ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Sudah Dibayar',
                    ]),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('markAsPaid')
                    ->label('Tandai Lunas')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'unpaid')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check-circle'),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellerEarnings::route('/'),
            // 'create' => Pages\CreateSellerEarnings::route('/create'),
            // 'edit' => Pages\EditSellerEarnings::route('/{record}/edit'),
        ];
    }
}
