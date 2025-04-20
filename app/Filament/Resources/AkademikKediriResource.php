<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Filament\Resources\AkademikKediriResource\Pages;
use App\Models\AkademikKediri;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AkademikKediriResource extends Resource
{
    protected static ?string $model = AkademikKediri::class;
    protected static ?string $slug = 'akademik-kediri';
    protected static ?string $modelLabel = 'Nilai Akademik';
    protected static ?string $pluralModelLabel = 'Nilai Akademik';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Nilai Akademik';
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
                Filter::make('kelompok')
                    ->form([
                        Select::make('kelompok')
                            ->label('Kelompok')
                            ->options(KelompokKediri::class),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['kelompok'],
                                fn (Builder $query): Builder =>
                                $query->whereHas('peserta', function ($query) use ($data) {
                                    $query->where('kelompok', $data['kelompok']);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['kelompok']) {
                            return null;
                        }

                        return 'Camp: ' . $data['kelompok'];
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
            'index' => Pages\ListAkademikKediris::route('/'),
            'create' => Pages\CreateAkademikKediri::route('/create'),
            'view' => Pages\ViewAkademikKediri::route('/{record}'),
            'edit' => Pages\EditAkademikKediri::route('/{record}/edit'),
        ];
    }
}
