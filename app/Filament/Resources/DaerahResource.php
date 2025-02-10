<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DaerahResource\Pages;
use App\Models\Daerah;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DaerahResource extends Resource
{
    protected static ?string $model = Daerah::class;
    protected static ?string $slug = 'daerah';
    protected static ?string $modelLabel = 'Daerah';
    protected static ?string $pluralModelLabel = 'Daerah';
    protected static ?string $recordTitleAttribute = 'n_daerah';
    protected static ?string $navigationLabel = 'Daerah';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Daerah::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Daerah::getColumns())
            ->defaultSort('id_daerah')
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
            'index' => Pages\ListDaerahs::route('/'),
            'create' => Pages\CreateDaerah::route('/create'),
            'view' => Pages\ViewDaerah::route('/{record}'),
            'edit' => Pages\EditDaerah::route('/{record}/edit'),
        ];
    }
}
