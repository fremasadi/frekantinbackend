<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Select;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Kelola Pengguna';

    public static function getModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Pengguna';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->unique(table: 'users', column: 'name', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Nama sudah digunakan.',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->maxLength(255)
                    ->required(fn (string $context) => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->same('password_confirmation') // validasi jika diisi
                    ->validationMessages([
                        'required' => 'Kata sandi wajib diisi saat pembuatan pengguna.',
                        'same' => 'Konfirmasi kata sandi tidak cocok.',
                    ]),
                
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Konfirmasi Kata Sandi')
                    ->password()
                    ->maxLength(255)
                    ->dehydrated(false) // tidak disimpan ke database
                    ->visible(fn ($get) => filled($get('password'))), // hanya tampil kalau password diisi
                       
                Forms\Components\TextInput::make('phone')
                    ->label('No.Telefon')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image')
                    ->placeholder('Klik pilih gambar pengguna')
                    ->label('Foto Jika Ada')
                    ->image(),
                    Select::make('role')
                    ->label('Peran')
                    ->required()
                    ->options([
                        'admin' => 'Admin',
                        'seller' => 'Penjual',
                        'customer' => 'Pelanggan',
                    ])
                    ->native(false),
                    Toggle::make('is_active')
                    ->label('Aktifkan Akun')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No.Telefon')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->searchable(),
                    IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
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
                // Tables\Actions\DeleteAction::make(),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
