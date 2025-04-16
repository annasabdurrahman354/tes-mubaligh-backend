<?php

namespace App\Filament\Imports;

use App\Models\Kecamatan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateNamaKecamatanImporter extends Importer
{
    protected static ?string $model = Kecamatan::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_kecamatan')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('kota_kab_id')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Kecamatan
    {
        return Kecamatan::firstOrNew([
            'id_kecamatan' => $this->data['id_kecamatan'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successfulRows = number_format($import->successful_rows);
        $failedRowsCount = $import->getFailedRowsCount();

        $body = "Proses impor kecamatan Anda telah selesai dan {$successfulRows} " . ($import->successful_rows > 1 ? 'baris' : 'baris') . " berhasil diimpor.";

        if ($failedRowsCount > 0) {
            $body .= " " . number_format($failedRowsCount) . " " . ($failedRowsCount > 1 ? 'baris' : 'baris') . " gagal diimpor.";
        }

        return $body;
    }
}
