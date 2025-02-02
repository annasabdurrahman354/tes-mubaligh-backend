<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkademikKediriResource\Pages;
use App\Models\AkademikKediri;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AkademikKediriResource extends Resource
{
    protected static ?string $model = AkademikKediri::class;
    protected static ?string $slug = 'akademik-kediri';
    protected static ?string $modelLabel = 'Nilai Penyampaian';
    protected static ?string $pluralModelLabel = 'Nilai Penyampaian';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Nilai Penyampaian';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(AkademikKediri::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(AkademikKediri::getColumns())
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
            'index' => Pages\ListAkademikKediris::route('/'),
            'create' => Pages\CreateAkademikKediri::route('/create'),
            'view' => Pages\ViewAkademikKediri::route('/{record}'),
            'edit' => Pages\EditAkademikKediri::route('/{record}/edit'),
        ];
    }
}
