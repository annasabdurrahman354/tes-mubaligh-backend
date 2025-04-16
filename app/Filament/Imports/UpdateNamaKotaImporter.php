<?php

namespace App\Filament\Imports;

use App\Models\Kota;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateNamaKotaImporter extends Importer
{
    protected static ?string $model = Kota::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_kota_kab')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('provinsi_id')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Kota
    {
        return Kota::firstOrNew([
            'id_kota_kab' => $this->data['id_kota_kab'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successfulRows = number_format($import->successful_rows);
        $failedRowsCount = $import->getFailedRowsCount();

        $body = "Proses impor kota Anda telah selesai dan {$successfulRows} " . ($import->successful_rows > 1 ? 'baris' : 'baris') . " berhasil diimpor.";

        if ($failedRowsCount > 0) {
            $body .= " " . number_format($failedRowsCount) . " " . ($failedRowsCount > 1 ? 'baris' : 'baris') . " gagal diimpor.";
        }

        return $body;
    }
}
