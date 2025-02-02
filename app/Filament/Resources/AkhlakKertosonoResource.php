<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkhlakKertosonoResource\Pages;
use App\Models\AkhlakKertosono;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AkhlakKertosonoResource extends Resource
{
    protected static ?string $model = AkhlakKertosono::class;
    protected static ?string $slug = 'akhlak-kertosono';
    protected static ?string $modelLabel = 'Catatan Akhlak';
    protected static ?string $pluralModelLabel = 'Catatan Akhlak';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Catatan Akhlak';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(AkhlakKertosono::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(AkhlakKertosono::getColumns())
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
            'index' => Pages\ListAkhlakKertosonos::route('/'),
            'create' => Pages\CreateAkhlakKertosono::route('/create'),
            'view' => Pages\ViewAkhlakKertosono::route('/{record}'),
            'edit' => Pages\EditAkhlakKertosono::route('/{record}/edit'),
        ];
    }
}
