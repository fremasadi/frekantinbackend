<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kelola Menu';

    public static function getModelLabel(): string
    {
        return 'Menu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Menu';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('seller_id')
                    ->label('Seller')
                    ->options(
                        User::where('role', 'seller')
                            ->get()
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),
                Select::make('category_id')
                    ->label('Kategory')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Menu')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Menu')                        
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Menu')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\FileUpload::make('image')
                    ->label('Gambar Menu')
                    ->image()
                    ->disk('public') // Gunakan disk 'public'
                    ->directory('product-images') // Simpan di folder 'category-images'
                    ->visibility('public'), // Atur visibilitas file menjadi publik
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Status') // Menampilkan status aktif atau tidak
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.name')
                ->label('Seller')
                ->sortable()
                ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Nama Kategory')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp.' . number_format($state, 0, ',', '.')) // Format ke IDR
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto Makanan')
                    ->size(100, 100),
                // Tables\Columns\TextColumn::make('stock')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->sortable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
