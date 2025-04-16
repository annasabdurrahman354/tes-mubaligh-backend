<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UpdateNamaKecamatanImporter;
use App\Filament\Resources\KecamatanResource\Pages\CreateKecamatan;
use App\Filament\Resources\KecamatanResource\Pages\EditKecamatan;
use App\Filament\Resources\KecamatanResource\Pages\ListKecamatans;
use App\Models\Kecamatan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;

class KecamatanResource extends Resource
{
    protected static ?string $model = Kecamatan::class;
    protected static ?string $slug = 'kecamatan';
    protected static ?string $modelLabel = 'Kecamatan';
    protected static ?string $pluralModelLabel = 'Kecamatan';
    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Kecamatan';
    protected static ?string $navigationGroup = 'Umum';
    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?int $navigationSort = 93;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Kecamatan::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Kecamatan::getColumns())
            ->defaultSort('id_kecamatan')
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
                    ->label('Update Kecamatan')
                    ->importer(UpdateNamaKecamatanImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('deleteAllKecamatan')
                    ->label('Delete All Kecamatan')
                    ->icon('heroicon-o-building-office') // Different icon example
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Kecamatan Data?')
                    ->modalDescription('WARNING: This will permanently delete ALL records from the Kecamatan table. This action cannot be undone. Are you sure?')
                    ->modalSubmitActionLabel('Yes, delete all Kecamatan')
                    ->action(function () {
                        try {
                            Kecamatan::query()->delete();
                            Notification::make()->title('Kecamatan Deleted')->body('All Kecamatan records have been deleted.')->success()->send();
                        } catch (QueryException $e) {
                            Notification::make()->title('Error Deleting Kecamatan')->body('Could not delete records. Check constraints or logs. Error: ' . $e->getMessage())->danger()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Unexpected Error')->body('Could not delete Kecamatan records. Check logs.')->danger()->send();
                        }
                    }),
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
            'index' => ListKecamatans::route('/'),
            'create' => CreateKecamatan::route('/create'),
            'edit' => EditKecamatan::route('/{record}/edit'),
        ];
    }
}
