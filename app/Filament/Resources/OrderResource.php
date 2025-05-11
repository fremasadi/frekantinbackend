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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Riwayat Pesanan';

    public static function getModelLabel(): string
    {
        return 'Pesanan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Pesanan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->disabled() // Ini akan menonaktifkan semua input dan menyembunyikan tombol Save/Cancel
            ->schema([
                Forms\Components\Placeholder::make('order_id')
                    ->label('Order ID')
                    ->content(fn ($record) => $record->order_id),
    
                Forms\Components\Placeholder::make('customer_name')
                    ->label('Customer Name')
                    ->content(fn ($record) => $record->customer?->name ?? 'Unknown'),
    
                Forms\Components\Placeholder::make('seller_name')
                    ->label('Seller Name')
                    ->content(fn ($record) => $record->seller?->name ?? 'Unknown'),
    
                Forms\Components\Placeholder::make('order_status')
                    ->label('Order Status')
                    ->content(fn ($record) => $record->order_status),
    
                Forms\Components\Placeholder::make('total_amount')
                    ->label('Total Amount')
                    ->content(fn ($record) => 'Rp ' . number_format($record->total_amount, 0, ',', '.')),
    
                Forms\Components\Placeholder::make('table_number')
                    ->label('Table Number')
                    ->content(fn ($record) => $record->table_number ?? 'N/A'),
    
                Forms\Components\Placeholder::make('estimated_delivery_time')
                    ->label('Estimated Delivery Time')
                    ->content(fn ($record) => $record->estimated_delivery_time?->format('d M Y H:i') ?? 'Not Set'),
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
                    ->formatStateUsing(fn ($state) => \App\Enums\OrderStatus::tryFrom($state)?->getLabel() ?? '-')
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
                Tables\Filters\SelectFilter::make('order_status')
                    ->label('Status Pesanan')
                    ->options([
                        'pending' => 'Menunggu',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->placeholder('Semua Status'),
            
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Penjual')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->placeholder('Semua Penjual'),
            
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
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
            // 'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
