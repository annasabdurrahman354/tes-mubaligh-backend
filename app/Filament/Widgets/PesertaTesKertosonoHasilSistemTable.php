<?php

namespace App\Filament\Widgets;

use App\Models\Ponpes;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PesertaTesKertosonoHasilSistemTable extends BaseWidget
{
    protected static ?int $sort = 99;
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Hasil Sistem Tes Kertosono')
            ->query(
                Ponpes::whereHas('pesertaKertosono', function ($query) {
                    $query->where('periode_id', getPeriodeTes());
                })
                ->withPesertaKertosonoHasilSistemCounts()
            )
            ->columns([
                Tables\Columns\TextColumn::make('n_ponpes')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('daerah.n_daerah')
                    ->label('Daerah')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('peserta_total')
                    ->label('Total')
                    ->sortable(),
                Tables\Columns\TextColumn::make('peserta_lulus')
                    ->label('Lulus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('peserta_tidak_lulus')
                    ->label('Tidak Lulus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('peserta_perlu_musyawarah')
                    ->label('Perlu Musyawarah')
                    ->sortable(),
            ])
            ->paginationPageOptions([5,10,25,50])
            ->defaultPaginationPageOption(5)
            ->defaultSort('n_ponpes');
    }
}
