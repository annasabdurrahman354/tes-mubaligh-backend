<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UpdateNamaKotaImporter;
use App\Filament\Resources\KotaResource\Pages\CreateKota;
use App\Filament\Resources\KotaResource\Pages\EditKota;
use App\Filament\Resources\KotaResource\Pages\ListKotas;
use App\Models\Kota;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;

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
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Update Kota')
                    ->importer(UpdateNamaKotaImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('deleteAllKota')
                    ->label('Delete All Kota')
                    ->icon('heroicon-o-building-office-2') // Different icon example
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Kota Data?')
                    ->modalDescription('WARNING: This will permanently delete ALL records from the Kota table. This action cannot be undone. Are you sure?')
                    ->modalSubmitActionLabel('Yes, delete all Kota')
                    ->action(function () {
                        try {
                            Kota::query()->delete();
                            Notification::make()->title('Kota Deleted')->body('All Kota records have been deleted.')->success()->send();
                        } catch (QueryException $e) {
                            Notification::make()->title('Error Deleting Kota')->body('Could not delete records. Check constraints or logs. Error: ' . $e->getMessage())->danger()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Unexpected Error')->body('Could not delete Kota records. Check logs.')->danger()->send();
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
            'index' => ListKotas::route('/'),
            'create' => CreateKota::route('/create'),
            'edit' => EditKota::route('/{record}/edit'),
        ];
    }
}
