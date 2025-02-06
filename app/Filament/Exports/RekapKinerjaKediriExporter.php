<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RekapKinerjaKediriExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('username')
                ->label('Nomor'),

            ExportColumn::make('nama')
                ->label('Nama'),

            ExportColumn::make('jumlah_penyimakan_putra_kediri')
                ->label('Penyimakan Putra'),

            ExportColumn::make('jumlah_penyimakan_putri_kediri')
                ->label('Penyimakan Putri'),

            ExportColumn::make('total_penyimakan_kediri')
                ->label('Total Penyimakan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        $body = 'Data hasil kinerja guru Kediri periode '.$periode['monthName'].' '.$periode['year'].' sejumlah ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' baris berhasil diekspor.';


        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
