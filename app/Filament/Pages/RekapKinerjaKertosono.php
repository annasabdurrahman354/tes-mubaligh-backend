<?php

namespace App\Filament\Pages;

use App\Filament\Exports\RekapKinerjaKertosonoExporter;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class RekapKinerjaKertosono extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'rekap-kinerja-kertosono';
    protected static ?string $title = 'Rekap Kinerja Kertosono';
    protected static ?string $navigationLabel = 'Rekap Kinerja';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.rekap-kinerja-kertosono';

    public function table(Table $table): Table
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        return $table
            ->query(
                User::query()
                    ->with(['roles'])
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Guru Kertosono');
                    })
            )
            ->description(fn() => 'Rekap Kinerja Tes Periode '.$periode['monthName'].' '.$periode['year'])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('username')
                    ->label('')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jumlah_penyimakan_putra_kertosono')
                    ->label('Penyimakan Putra'),

                TextColumn::make('jumlah_penyimakan_putri_kertosono')
                    ->label('Penyimakan Putri'),

                TextColumn::make('total_penyimakan_kertosono')
                    ->label('Total Penyimakan'),

                TextColumn::make('total_durasi_penilaian_kertosono')
                    ->label('Total Durasi Penilaian'),

                TextColumn::make('rata_rata_durasi_penilaian_kertosono')
                    ->label('Rata-rata Durasi Penilaian'),
            ])
            ->defaultSort('username')
            ->filters([

            ], layout: FiltersLayout::AboveContent)
            ->actions([

            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor')
                    ->exporter(RekapKinerjaKertosonoExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles'])
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Guru Kertosono');
                        })
                    )
            ])
            ->bulkActions([

            ]);
    }

}
