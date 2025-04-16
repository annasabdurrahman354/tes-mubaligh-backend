<?php

namespace App\Filament\Exports;

use App\Models\Ponpes;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PonpesExporter extends Exporter
{
    protected static ?string $model = Ponpes::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id_ponpes')
                ->label('ID'),
            ExportColumn::make('n_ponpes')
                ->label('Nama'),
            ExportColumn::make('id_daerah')
                ->label('ID Daerah'),
            ExportColumn::make('daerah.n_daerah')
                ->label('Nama Daerah'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Data ponpes sejumlah ' . number_format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . ' berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
