<?php

namespace App\Filament\Pages;

use App\Filament\Exports\RekapKinerjaKediriExporter;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class RekapKinerjaKediri extends Page implements HasTable
{
    use HasPageShield;
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

                TextColumn::make('total_durasi_penilaian_kediri')
                    ->label('Total Durasi Penilaian')
                    ->formatStateUsing(function ($state): string {
                        // Ensure the state is a valid number, otherwise return a default
                        if (!is_numeric($state) || $state < 0) {
                            return '0 menit'; // Or '-', or 'N/A' depending on preference
                        }

                        $totalMinutes = (int) $state; // Cast to integer just in case

                        if ($totalMinutes < 60) {
                            // If less than 60 minutes, just append 'menit'
                            return $totalMinutes . ' menit';
                        } else {
                            // Calculate hours and remaining minutes
                            $jam = intdiv($totalMinutes, 60); // Integer division for hours
                            $menit = $totalMinutes % 60;    // Modulo for remaining minutes

                            // Build the formatted string
                            $formattedString = $jam . ' jam';
                            if ($menit > 0) {
                                // Only add minutes part if there are remaining minutes
                                $formattedString .= ' ' . $menit . ' menit';
                            }
                            return $formattedString;
                        }
                    }),

                TextColumn::make('rata_rata_durasi_penilaian_kediri')
                    ->label('Rata-Rata Durasi Penilaian')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' menit'),
            ])
            ->defaultSort('username')
            ->filters([
                Filter::make('akademik_kediri_created_at') // Unique key for the filter
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dari'), // Label for start date
                        DatePicker::make('created_until')
                            ->label('Sampai'), // Label for end date
                    ])
                    ->query(function ($query, array $data) {
                        $from = $data['created_from'] ?? null;
                        $until = $data['created_until'] ?? null;

                        // Apply filter only if dates are present
                        if ($from || $until) {
                            // Use whereHas to filter Users based on the 'akademikKediri' relationship's created_at
                            return $query->whereHas('akademikKediri', function ($relationshipQuery) use ($from, $until) {
                                if ($from) {
                                    $relationshipQuery->whereDate('created_at', '>=', $from);
                                }
                                if ($until) {
                                    $relationshipQuery->whereDate('created_at', '<=', $until);
                                }
                            });
                        }

                        return $query;
                    })
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
