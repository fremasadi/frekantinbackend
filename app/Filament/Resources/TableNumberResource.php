<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableNumberResource\Pages;
use App\Filament\Resources\TableNumberResource\RelationManagers;
use App\Models\TableNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableNumberResource extends Resource
{
    protected static ?string $model = TableNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';

    protected static ?string $navigationLabel = 'Kelola Meja';

    public static function getModelLabel(): string
    {
        return 'Meja';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Meja';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->label('Nomer')
                    ->required()
                    ->unique(table: 'table_numbers', column: 'number', ignoreRecord: true)
                    ->rule('unique:table_numbers,number')
                    ->validationMessages([
                        'unique' => 'Nomer Meja sudah digunakan.',
                    ])
                    ->maxLength(255),
                Forms\Components\Toggle::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Meja')
                    ->formatStateUsing(fn ($state) => 'Meja ' . $state)
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
            
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('qrcode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->action(function ($record) {
                        $number = $record->number;

                        $qr = base64_encode(QrCode::format('png')->size(200)->generate($number));
                        $url = 'data:image/png;base64,' . $qr;

                        return response()->streamDownload(function () use ($url) {
                            echo base64_decode(str_replace('data:image/png;base64,', '', $url));
                        }, 'qrcode_meja_' . $number . '.png');
                    })
                    ->requiresConfirmation()
                    ->color('primary'),



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
            'index' => Pages\ListTableNumbers::route('/'),
            'create' => Pages\CreateTableNumber::route('/create'),
            'edit' => Pages\EditTableNumber::route('/{record}/edit'),
        ];
    }
}
