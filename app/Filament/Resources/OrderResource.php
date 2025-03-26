<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers\OrderItemsRelationManager;
use Filament\Tables\Actions\Action;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('customer_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('seller_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('order_status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('table_number')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('estimated_delivery_time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Reference Id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name') // Ambil nama customer dari relasi
                    ->label('Nama Pembeli')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.name') // Ambil nama seller dari relasi
                    ->label('Nama Penjual')
                    ->searchable()
                    ->sortable(),                
                Tables\Columns\TextColumn::make('order_status')
                    ->label('Status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Harga')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('table_number')
                    ->label('Nomor Meja')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Action::make('viewItems')
                    ->label('Lihat Items')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Items Order #'.$record->order_id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record) {
                        return view('filament.orders.items-table', [
                            'items' => $record->orderItems()->with('product')->get()
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
