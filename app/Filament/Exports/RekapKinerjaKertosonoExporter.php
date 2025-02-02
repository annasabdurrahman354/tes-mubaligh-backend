<?php

namespace App\Filament\Exports;

use App\Models\User;
use App\Settings\PengaturanTes;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RekapKinerjaKertosonoExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('username')
                ->label('Nomor'),

            ExportColumn::make('nama')
                ->label('Nama'),

            ExportColumn::make('jumlah_penyimakan_putra')
                ->label('Penyimakan Putra'),

            ExportColumn::make('jumlah_penyimakan_putri')
                ->label('Penyimakan Putri'),

            ExportColumn::make('total_penyimakan')
                ->label('Total Penyimakan'),

            ExportColumn::make('total_durasi_penilaian')
                ->label('Total Durasi Penilaian'),

            ExportColumn::make('rata_rata_durasi_penilaian')
                ->label('Rata-rata Durasi Penilaian'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $periode_pengetesan_id = app(PengaturanTes::class)->periode_pengetesan_id;
        $periode = getYearAndMonthName($periode_pengetesan_id);

        $body = 'Data hasil kinerja guru Kertosono periode '.$periode['monthName'].' '.$periode['year'].' sejumlah ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' baris berhasil diekspor.';


        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
