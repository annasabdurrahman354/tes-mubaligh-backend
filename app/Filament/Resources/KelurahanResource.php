<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UpdateKelurahanImporter;
use App\Filament\Resources\KelurahanResource\Pages\CreateKelurahan;
use App\Filament\Resources\KelurahanResource\Pages\EditKelurahan;
use App\Filament\Resources\KelurahanResource\Pages\ListKelurahans;
use App\Models\Kelurahan;
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
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Update Kelurahan')
                    ->importer(UpdateKelurahanImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('deleteAllKelurahan')
                    ->label('Delete All Kelurahan')
                    ->icon('heroicon-o-home-modern') // Different icon example
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Kelurahan Data?')
                    ->modalDescription('WARNING: This will permanently delete ALL records from the Kelurahan table. This action cannot be undone. Are you sure?')
                    ->modalSubmitActionLabel('Yes, delete all Kelurahan')
                    ->action(function () {
                        try {
                            Kelurahan::query()->delete();
                            Notification::make()->title('Kelurahan Deleted')->body('All Kelurahan records have been deleted.')->success()->send();
                        } catch (QueryException $e) {
                            Notification::make()->title('Error Deleting Kelurahan')->body('Could not delete records. Check constraints or logs. Error: ' . $e->getMessage())->danger()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Unexpected Error')->body('Could not delete Kelurahan records. Check logs.')->danger()->send();
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
            'index' => ListKelurahans::route('/'),
            'create' => CreateKelurahan::route('/create'),
            'edit' => EditKelurahan::route('/{record}/edit'),
        ];
    }
}
