<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KotaResource\Pages\CreateKota;
use App\Filament\Resources\KotaResource\Pages\EditKota;
use App\Filament\Resources\KotaResource\Pages\ListKotas;
use App\Models\Kota;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KotaResource extends Resource
{
    protected static ?string $model = Kota::class;
    protected static ?string $slug = 'kota';
    protected static ?string $modelLabel = 'Kota';
    protected static ?string $pluralModelLabel = 'Kota';
    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Kota';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?int $navigationSort = 92;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Kota::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Kota::getColumns())
            ->defaultSort('id_kota_kab')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->selectCurrentPageOnly();
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
            'index' => ListKotas::route('/'),
            'create' => CreateKota::route('/create'),
            'edit' => EditKota::route('/{record}/edit'),
        ];
    }
}
