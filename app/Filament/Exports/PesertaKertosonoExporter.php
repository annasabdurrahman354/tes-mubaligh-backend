<?php

namespace App\Filament\Exports;

use App\Models\PesertaKertosono;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PesertaKertosonoExporter extends Exporter
{
    protected static ?string $model = PesertaKertosono::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id_periode')
                ->label('Periode'),

            ExportColumn::make('nispn')
                ->label('NISPN'),

            ExportColumn::make('siswa.nik')
                ->label('NIK'),

            ExportColumn::make('nomor_cocard')
                ->label('Cocard'),

            ExportColumn::make('siswa.nama_lengkap')
                ->label('Nama'),

            ExportColumn::make('siswa.nama_panggilan')
                ->label('Nama Panggilan'),

            ExportColumn::make('siswa.jenis_kelamin')
                ->label('Jenis Kelamin')
                ->state(function (PesertaKertosono $record) {
                    return $record->siswa->jenis_kelamin->value;
                }),

            ExportColumn::make('ponpes.n_ponpes')
                ->label('Ponpes'),

            ExportColumn::make('ponpes.daerah.n_daerah')
                ->label('Daerah Ponpes'),

            ExportColumn::make('siswa.tempat_lahir')
                ->label('Tempat Lahir'),

            ExportColumn::make('siswa.tanggal_lahir')
                ->label('Tanggal Lahir'),

            ExportColumn::make('umur')
                ->label('Umur')
                ->state(function (PesertaKertosono $record) {
                    $tanggalLahir = $record->siswa->tanggal_lahir;
                    return Carbon::parse($tanggalLahir)->age;
                }),

            ExportColumn::make('siswa.hp')
                ->label('HP'),

            ExportColumn::make('siswa.alamat')
                ->label('Alamat'),

            ExportColumn::make('siswa.provinsi')
                ->label('Provinsi'),

            ExportColumn::make('siswa.kota_kab')
                ->label('Kota'),

            ExportColumn::make('siswa.kecamatan')
                ->label('Kecamatan'),

            ExportColumn::make('siswa.desa_kel')
                ->label('Kelurahan'),

            ExportColumn::make('siswa.rt')
                ->label('RT'),

            ExportColumn::make('siswa.rw')
                ->label('RW'),

            ExportColumn::make('siswa.kode_pos')
                ->label('Kode Pos'),

            ExportColumn::make('siswa.daerahSambung.n_daerah')
                ->label('Asal Daerah'),

            ExportColumn::make('siswa.desa_sambung')
                ->label('Asal Desa'),

            ExportColumn::make('siswa.kelompok_sambung')
                ->label('Asal Kelompok'),

            ExportColumn::make('siswa.status_mondok')
                ->label('Status Mondok')
                ->state(function (PesertaKertosono $record) {
                    return $record->siswa->status_mondok?->getLabel();
                }),

            ExportColumn::make('siswa.daerahKiriman.n_daerah')
                ->label('Kiriman Daerah'),

            ExportColumn::make('siswa.pendidikan')
                ->label('Pendidikan'),

            ExportColumn::make('siswa.jurusan')
                ->label('Jurusan'),

            ExportColumn::make('siswa.keahlian')
                ->label('Keahlian'),

            ExportColumn::make('siswa.hobi')
                ->label('Hobi'),

            ExportColumn::make('siswa.bahasa_makna')
                ->label('Bahasa Makna'),

            ExportColumn::make('siswa.bahasa_harian')
                ->label('Bahasa Harian'),

            ExportColumn::make('siswa.sim')
                ->label('SIM'),

            ExportColumn::make('siswa.riwayat_sakit')
                ->label('Riwayat Sakit'),

            ExportColumn::make('siswa.nama_ayah')
                ->label('Nama Ayah'),

            ExportColumn::make('siswa.nama_ibu')
                ->label('Nama Ibu'),

            ExportColumn::make('rekomendasi_guru')
                ->label('Direkomendasikan')
                ->state(function (PesertaKertosono $record) {
                    return $record->rekomendasi_guru;
                }),

            ExportColumn::make('status_tes')
                ->label('Status Tes')
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
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        $body = 'Data hasil tes Kertosono periode '.$periode['monthName'].' '.$periode['year'].' sejumlah ' . number_format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . ' berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
