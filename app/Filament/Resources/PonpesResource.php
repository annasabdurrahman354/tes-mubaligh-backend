<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PondokResource\Pages\CreatePondok;
use App\Filament\Resources\PondokResource\Pages\EditPondok;
use App\Filament\Resources\PondokResource\Pages\ListPondok;
use App\Filament\Resources\PondokResource\Pages\ViewPondok;
use App\Models\Ponpes;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PonpesResource extends Resource
{
    protected static ?string $model = Ponpes::class;
    protected static ?string $slug = 'pondok';
    protected static ?string $modelLabel = 'Pondok';
    protected static ?string $pluralModelLabel = 'Pondok';
    protected static ?string $recordTitleAttribute = 'nama';
    protected static ?string $navigationLabel = 'Pondok Pesantren';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Ponpes::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Ponpes::getColumns())
            ->defaultSort('id_ponpes')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => ListPondok::route('/'),
            'create' => CreatePondok::route('/create'),
            'view' => ViewPondok::route('/{record}'),
            'edit' => EditPondok::route('/{record}/edit'),
        ];
    }
}
