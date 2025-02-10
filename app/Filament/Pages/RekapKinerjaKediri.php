<?php

namespace App\Filament\Pages;

use App\Filament\Exports\RekapKinerjaKediriExporter;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class RekapKinerjaKediri extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'rekap-kinerja-kediri';
    protected static ?string $title = 'Rekap Kinerja Kediri';
    protected static ?string $navigationLabel = 'Rekap Kinerja';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static string $view = 'filament.pages.rekap-kinerja-kediri';

    public function table(Table $table): Table
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        return $table
            ->query(
                User::query()
                    ->with(['roles'])
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Guru Kediri');
                    })
            )
            ->description(fn() => 'Rekap Kinerja Tes Periode '.$periode['monthName'].' '.$periode['year'])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('username')
                    ->label('Username')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jumlah_penyimakan_putra_kediri')
                    ->label('Penyimakan Putra'),

                TextColumn::make('jumlah_penyimakan_putri_kediri')
                    ->label('Penyimakan Putri'),

                TextColumn::make('total_penyimakan_kediri')
                    ->label('Total Penyimakan'),
            ])
            ->defaultSort('username')
            ->filters([

            ], layout: FiltersLayout::AboveContent)
            ->actions([

            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor')
                    ->exporter(RekapKinerjaKediriExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles'])
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Guru Kediri');
                        })
                    )
            ])
            ->bulkActions([

            ])
            ->striped();
    }

}
