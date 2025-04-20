<?php

namespace App\Filament\Imports;

use App\Models\PesertaKediri;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UpdateRFIDPesertaKediriImporter extends Importer
{
    protected static ?string $model = PesertaKediri::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id_periode')
                ->label('ID Periode')
                ->requiredMapping()
                ->rules(['required'])
                ->example('202504'),

            ImportColumn::make('kelompok')
                ->label('Kelompok')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example(['A','B','C','D']),

            ImportColumn::make('nomor_cocard')
                ->label('Nomor Cocard')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example([1,2,3,4]),

            ImportColumn::make('rfid')
                ->label('RFID')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example('10 DIGIT'),
        ];
    }

    /**
     * Resolve the PesertaKediri record based on composite keys.
     * This importer ONLY updates existing records.
     *
     * @return PesertaKediri|null
     * @throws RowImportFailedException
     */
    public function resolveRecord(): ?PesertaKediri
    {
        // Access mapped data for the current row
        $idPeriode = $this->data['id_periode'];
        $kelompok = $this->data['kelompok'];
        $nomorCocard = $this->data['nomor_cocard'];

        // Find the PesertaKediri record using the composite key
        $peserta = PesertaKediri::query()
            ->where('id_periode', $idPeriode)
            ->where('kelompok', $kelompok)
            ->where('nomor_cocard', $nomorCocard)
            ->first();

        // If no matching PesertaKediri record is found, fail this row import
        if (! $peserta) {
            throw new RowImportFailedException("Peserta Kediri not found with Periode=[{$idPeriode}], Kelompok=[{$kelompok}], CoCard=[{$nomorCocard}].");
            // Alternatively, you could just `return null;` to silently skip rows
            // without a match, but failing is usually more informative.
        }

        // Return the found record. Filament will attempt to fill/save it.
        return $peserta;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your peserta kediri import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
