<?php

namespace App\Filament\Imports;

use App\Models\Kelurahan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateKelurahanImporter extends Importer
{
    protected static ?string $model = Kelurahan::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_desa_kel')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('kecamatan_id')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Kelurahan
    {
        return Kelurahan::firstOrNew([
            'id_desa_kel' => $this->data['id_desa_kel'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successfulRows = number_format($import->successful_rows);
        $failedRowsCount = $import->getFailedRowsCount();

        $body = "Proses impor kelurahan Anda telah selesai dan {$successfulRows} " . ($import->successful_rows > 1 ? 'baris' : 'baris') . " berhasil diimpor.";

        if ($failedRowsCount > 0) {
            $body .= " " . number_format($failedRowsCount) . " " . ($failedRowsCount > 1 ? 'baris' : 'baris') . " gagal diimpor.";
        }

        return $body;
    }
}
