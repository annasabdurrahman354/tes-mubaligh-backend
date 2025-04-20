<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Filament\Resources\AkhlakKertosonoResource\Pages;
use App\Models\AkhlakKertosono;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Filter::make('jenis_kelamin')
                    ->form([
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jenis_kelamin'],
                                fn (Builder $query): Builder =>
                                $query->whereHas('peserta.siswa', function ($query) use ($data) {
                                    $query->where('jenis_kelamin', $data['jenis_kelamin']);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['jenis_kelamin']) {
                            return null;
                        }

                        return 'Jenis Kelamin: ' . $data['jenis_kelamin'];
                    }),
            ], layout: FiltersLayout::AboveContent)
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
            'index' => Pages\ListAkhlakKertosonos::route('/'),
            'create' => Pages\CreateAkhlakKertosono::route('/create'),
            'view' => Pages\ViewAkhlakKertosono::route('/{record}'),
            'edit' => Pages\EditAkhlakKertosono::route('/{record}/edit'),
        ];
    }
}
