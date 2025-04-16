<?php

namespace App\Filament\Imports;

use App\Models\Provinsi;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateNamaProvinsiImporter extends Importer
{
    protected static ?string $model = Provinsi::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_provinsi')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Provinsi
    {
        return Provinsi::firstOrNew([
            'id_provinsi' => $this->data['id_provinsi'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successfulRows = number_format($import->successful_rows);
        $failedRowsCount = $import->getFailedRowsCount();

        $body = "Proses impor provinsi Anda telah selesai dan {$successfulRows} " . ($import->successful_rows > 1 ? 'baris' : 'baris') . " berhasil diimpor.";

        if ($failedRowsCount > 0) {
            $body .= " " . number_format($failedRowsCount) . " " . ($failedRowsCount > 1 ? 'baris' : 'baris') . " gagal diimpor.";
        }

        return $body;
    }
}
