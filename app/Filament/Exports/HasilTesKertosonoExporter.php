<?php

namespace App\Filament\Exports;

use App\Models\PesertaKertosono;
use App\Settings\PengaturanTes;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class HasilTesKertosonoExporter extends Exporter
{
    protected static ?string $model = PesertaKertosono::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('periode_id')
                ->label('Periode'),

            ExportColumn::make('nomor_cocard')
                ->label('Cocard'),

            ExportColumn::make('siswa.nama')
                ->label('Nama'),

            ExportColumn::make('siswa.jenis_kelamin')
                ->label('Jenis Kelamin')
                ->state(function (PesertaKertosono $record) {
                    return $record->siswa->jenis_kelamin->getLabel();
                }),

            ExportColumn::make('siswa.asalPondok.nama')
                ->label('Asal Pondok'),

            ExportColumn::make('siswa.tempatLahir.nama')
                ->label('Tempat Lahir'),

            ExportColumn::make('siswa.tanggal_lahir')
                ->label('Tanggal Lahir'),

            ExportColumn::make('umur')
                ->label('Umur')
                ->state(function (PesertaKertosono $record) {
                    $tanggalLahir = $record->siswa->tanggal_lahir;
                    return Carbon::parse($tanggalLahir)->age;
                }),

            ExportColumn::make('siswa.alamat')
                ->label('Alamat'),

            ExportColumn::make('siswa.asalDaerah.nama')
                ->label('Asal Daerah'),

            ExportColumn::make('siswa.asal_desa')
                ->label('Asal Desa'),

            ExportColumn::make('siswa.asal_kelompok')
                ->label('Asal Kelompok'),

            ExportColumn::make('siswa.status_mondok')
                ->label('Status Mondok')
                ->state(function (PesertaKertosono $record) {
                    return $record->siswa->status_mondok?->getLabel();
                }),

            ExportColumn::make('status_tes')
                ->label('Hasil Tes')
                ->state(function (PesertaKertosono $record) {
                    return $record->status_tes?->getLabel();
                }),

            ExportColumn::make('status_kelanjutan')
                ->label('Status Kelajutan')
                ->state(function (PesertaKertosono $record) {
                    return $record->status_kelanjutan?->getLabel();
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $periode_pengetesan_id = app(PengaturanTes::class)->periode_pengetesan_id;
        $periode = getYearAndMonthName($periode_pengetesan_id);

        $body = 'Data hasil tes Kertosono periode '.$periode['monthName'].' '.$periode['year'].' sejumlah ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
