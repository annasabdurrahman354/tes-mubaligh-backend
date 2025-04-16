<?php

namespace App\Filament\Imports;

use App\Models\PesertaKediri;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateStatusTesPesertaKediriImporter extends Importer
{
    protected static ?string $model = PesertaKediri::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_periode')
                ->label('Periode')
                ->requiredMapping(),
            ImportColumn::make('tahap')
                ->label('Tahap')
                ->requiredMapping(),
            ImportColumn::make('nispn')
                ->label('NISPN')
                ->requiredMapping(),
            ImportColumn::make('status_tes')
                ->label('Status Tes')
                ->rules(['nullable']), // Adjust rules as needed
            ImportColumn::make('status_kelanjutan')
                ->label('Status Kelanjutan')
                ->rules(['nullable']), // Adjust rules as needed
        ];
    }

    public function resolveRecord(): ?PesertaKediri
    {
        return PesertaKediri::query()
            ->where('id_periode', $this->data['id_periode'])
            ->where('tahap', $this->data['tahap'])
            ->where('nispn', $this->data['nispn'])
            ->first();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Upload hasil tes kediri telah selesai dan ' . number_format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diperbarui.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diperbarui.';
        }

        return $body;
    }
}
