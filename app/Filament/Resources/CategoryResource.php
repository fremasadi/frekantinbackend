<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kelola Kategori';

    public static function getModelLabel(): string
    {
        return 'Kategori';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Kategori';
    }
    


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->unique(table: 'categories', column: 'name', ignoreRecord: true)
                    ->maxLength(255),
                    Forms\Components\FileUpload::make('image')
                    ->label('Foto Kategori')
                    ->image()
                    ->disk('public')
                    ->directory('category-images')
                    ->visibility('public')
                    ->placeholder('Klik pilih gambar kategori')
                    ->previewable(true)
                    ->downloadable(true)
                    ->openable(true)
                    ->panelLayout('grid') // Tampilan grid untuk pratinjau
                    ->appendFiles() // Tambahkan file baru tanpa menghapus yang lama
                    ->maxFiles(1) // Batasi hanya 1 file
                    ->moveFiles() // Pindahkan file ke direktori yang ditentukan
            ]);
    }

    public static function getActions(): array
{
    return [
        CreateAction::make()
            ->label('New Kategori')
            ->icon('heroicon-o-plus') // Ikon plus
            ->color('primary')
            // Konfigurasi untuk tampilan mobile
            ->floatRight() // Posisi di kanan bawah
            ->size('lg') // Ukuran besar
            ->circular() // Tombol berbentuk bulat
    ];
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->size(50, 50)
                    ->label('Foto Kategori'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->products()->count() === 0),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
