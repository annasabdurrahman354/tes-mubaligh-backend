<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UpdateNamaProvinsiImporter;
use App\Filament\Resources\ProvinsiResource\Pages\CreateProvinsi;
use App\Filament\Resources\ProvinsiResource\Pages\EditProvinsi;
use App\Filament\Resources\ProvinsiResource\Pages\ListProvinsis;
use App\Models\Provinsi;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;

class ProvinsiResource extends Resource
{
    protected static ?string $model = Provinsi::class;
    protected static ?string $slug = 'provinsi';
    protected static ?string $modelLabel = 'Provinsi';
    protected static ?string $pluralModelLabel = 'Provinsi';
    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Provinsi';
    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?int $navigationSort = 91;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Provinsi::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Provinsi::getColumns())
            ->defaultSort('id_provinsi')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Update Provinsi')
                    ->importer(UpdateNamaProvinsiImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('deleteAllProvinsi')
                    ->label('Delete All Provinsi')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Provinsi Data?')
                    ->modalDescription('WARNING: This will permanently delete ALL records from the Provinsi table. This action cannot be undone. Are you sure?')
                    ->modalSubmitActionLabel('Yes, delete all Provinsi')
                    ->action(function () {
                        try {
                            Provinsi::query()->delete();
                            Notification::make()->title('Provinsi Deleted')->body('All Provinsi records have been deleted.')->success()->send();
                        } catch (QueryException $e) {
                            Notification::make()->title('Error Deleting Provinsi')->body('Could not delete records. Check constraints or logs. Error: ' . $e->getMessage())->danger()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Unexpected Error')->body('Could not delete Provinsi records. Check logs.')->danger()->send();
                        }
                    })
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
            'index' => ListProvinsis::route('/'),
            'create' => CreateProvinsi::route('/create'),
            'edit' => EditProvinsi::route('/{record}/edit'),
        ];
    }
}
