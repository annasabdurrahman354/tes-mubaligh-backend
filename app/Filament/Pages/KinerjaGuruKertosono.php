<?php

namespace App\Filament\Pages;

use App\Filament\Exports\KinerjaGuruKertosonoExporter;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Pages\Page;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class KinerjaGuruKertosono extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $slug = 'kinerja-guru-kertosono';
    protected static ?string $title = 'Kinerja Guru Kertosono';
    protected static ?string $navigationLabel = 'Kinerja Guru';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static string $view = 'filament.pages.kinerja-guru-kertosono';

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
            ->description(fn() => 'Kinerja Guru Tes Periode '.$periode['monthName'].' '.$periode['year'])
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

                TextColumn::make('jumlah_penyimakan_putra_kertosono')
                    ->label('Penyimakan Putra'),

                TextColumn::make('jumlah_penyimakan_putri_kertosono')
                    ->label('Penyimakan Putri'),

                TextColumn::make('total_penyimakan_kertosono')
                    ->label('Total Penyimakan'),

                TextColumn::make('total_durasi_penilaian_kertosono')
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

                TextColumn::make('rata_rata_durasi_penilaian_kertosono')
                    ->label('Rata-Rata Durasi Penilaian')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' menit'),
            ])
            ->defaultSort('username')
            ->filters([
                Filter::make('akademik_kertosono_created_at') // Unique key for the filter
                    ->form([
                        Fieldset::make()
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label('Mulai tgl'),
                                DatePicker::make('created_until')
                                    ->label('Sampai Tgl')
                            ])
                            ->columnSpanFull()
                    ])
                    ->columnSpanFull()
                    ->query(function ($query, array $data) {
                        $from = $data['created_from'] ?? null;
                        $until = $data['created_until'] ?? null;

                        // Apply filter only if dates are present
                        if ($from || $until) {
                            // Use whereHas to filter Users based on the 'akademikKertosono' relationship's created_at
                            return $query->whereHas('akademikKertosono', function ($relationshipQuery) use ($from, $until) {
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
                    ->exporter(KinerjaGuruKertosonoExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles'])
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Guru Kertosono');
                        })
                    )
            ])
            ->bulkActions([

            ])
            ->striped();
    }

}
