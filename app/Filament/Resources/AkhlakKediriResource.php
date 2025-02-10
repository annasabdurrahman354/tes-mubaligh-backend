<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkhlakKediriResource\Pages;
use App\Models\AkhlakKediri;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AkhlakKediriResource extends Resource
{
    protected static ?string $model = AkhlakKediri::class;
    protected static ?string $slug = 'akhlak-kediri';
    protected static ?string $modelLabel = 'Catatan Akhlak';
    protected static ?string $pluralModelLabel = 'Catatan Akhlak';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Catatan Akhlak';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(AkhlakKediri::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(AkhlakKediri::getColumns())
            ->defaultSort('peserta.siswa.nama_lengkap')
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
            'index' => Pages\ListAkhlakKediris::route('/'),
            'create' => Pages\CreateAkhlakKediri::route('/create'),
            'view' => Pages\ViewAkhlakKediri::route('/{record}'),
            'edit' => Pages\EditAkhlakKediri::route('/{record}/edit'),
        ];
    }
}
