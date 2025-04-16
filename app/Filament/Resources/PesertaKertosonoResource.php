<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Filament\Exports\PesertaKertosonoExporter;
use App\Filament\Resources\PesertaKertosonoResource\Pages\CreatePesertaKertosono;
use App\Filament\Resources\PesertaKertosonoResource\Pages\EditPesertaKertosono;
use App\Filament\Resources\PesertaKertosonoResource\Pages\ListPesertaKertosonos;
use App\Filament\Resources\PesertaKertosonoResource\Pages\ViewPesertaKertosono;
use App\Models\Periode;
use App\Models\PesertaKertosono;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PesertaKertosonoResource extends Resource
{
    protected static ?string $model = PesertaKertosono::class;
    protected static ?string $slug = 'peserta-kertosono';
    protected static ?string $modelLabel = 'Peserta Kertosono';
    protected static ?string $pluralModelLabel = 'Peserta Kertosono';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Peserta Kertosono';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(PesertaKertosono::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PesertaKertosono::getColumns())
            ->defaultSort('nomor_cocard')
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
                                $query->whereHas('siswa', function ($query) use ($data) {
                                    $query->where('jenis_kelamin', $data['jenis_kelamin']);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['jenis_kelamin']) {
                            return null;
                        }

                        $jenis_kelamin = $data['jenis_kelamin'] == JenisKelamin::LAKI_LAKI->value ? 'Laki-laki' : 'Perempuan';

                        return 'Jenis Kelamin: ' . $jenis_kelamin;
                    }),
                SelectFilter::make('id_periode')
                    ->label('Periode Tes')
                    ->options(Periode::orderBy('id_periode', 'desc')->get()->pluck('id_periode', 'id_periode'))
                    ->searchable()
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Action::make('transfer_peserta')
                    ->label('Transfer Peserta')
                    ->action(function () {
                        PesertaKertosono::createKertosonoParticipantsFromPreviousMonth();
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('assign_cocard')
                    ->label('Assign Cocard')
                    ->action(function () {
                        PesertaKertosono::updateNomorCocard();
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                ExportAction::make()
                    ->label('Ekspor')
                    ->exporter(PesertaKertosonoExporter::class)
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
            'index' => ListPesertaKertosonos::route('/'),
            'create' => CreatePesertaKertosono::route('/create'),
            'view' => ViewPesertaKertosono::route('/{record}'),
            'edit' => EditPesertaKertosono::route('/{record}/edit'),
        ];
    }
}
