<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelurahanResource\Pages\CreateKelurahan;
use App\Filament\Resources\KelurahanResource\Pages\EditKelurahan;
use App\Filament\Resources\KelurahanResource\Pages\ListKelurahans;
use App\Models\Kelurahan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KelurahanResource extends Resource
{
    protected static ?string $model = Kelurahan::class;
    protected static ?string $slug = 'kelurahan';
    protected static ?string $modelLabel = 'Kelurahan';
    protected static ?string $pluralModelLabel = 'Kelurahan';
    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Kelurahan';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?int $navigationSort = 94;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Kelurahan::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Kelurahan::getColumns())
            ->defaultSort('id_desa_kel')
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
            ->selectCurrentPageOnly()
            ->striped();
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
            'index' => ListKelurahans::route('/'),
            'create' => CreateKelurahan::route('/create'),
            'edit' => EditKelurahan::route('/{record}/edit'),
        ];
    }
}
