<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkademikKertosonoResource\Pages;
use App\Models\AkademikKertosono;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AkademikKertosonoResource extends Resource
{
    protected static ?string $model = AkademikKertosono::class;
    protected static ?string $slug = 'akademik-kertosono';
    protected static ?string $modelLabel = 'Nilai Bacaan';
    protected static ?string $pluralModelLabel = 'Nilai Bacaan';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Nilai Bacaan';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(AkademikKertosono::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(AkademikKertosono::getColumns())
            ->defaultSort('peserta.nomor_cocard')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
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
            'index' => Pages\ListAkademikKertosonos::route('/'),
            'create' => Pages\CreateAkademikKertosono::route('/create'),
            'view' => Pages\ViewAkademikKertosono::route('/{record}'),
            'edit' => Pages\EditAkademikKertosono::route('/{record}/edit'),
        ];
    }
}
