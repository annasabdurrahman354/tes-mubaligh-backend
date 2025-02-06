<?php

namespace App\Filament\Pages;

use App\Enums\HasilSistem;
use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Enums\StatusKelanjutanKediri;
use App\Enums\StatusTesKediri;
use App\Filament\Exports\HasilTesKediriExporter;
use App\Models\PesertaKediri;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HasilTesKediri extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'hasil-tes-kediri';
    protected static ?string $title = 'Hasil Tes Kediri';
    protected static ?string $navigationLabel = 'Hasil Tes';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.hasil-tes-kediri';

    public function table(Table $table): Table
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        return $table
            ->query(
                PesertaKediri::query()
                    ->with('siswa')
                    ->withHasilSistem()
                    ->where('periode_id', $periode_pengetesan_id)
                    ->whereNotIn('status_tes', [
                        StatusTesKediri::PRA_TES->value,
                        StatusTesKediri::APPROVED_BARANG->value,
                        StatusTesKediri::APPROVED_MATERI->value,
                        StatusTesKediri::REJECTED_BARANG->value,
                        StatusTesKediri::REJECTED_MATERI->value,
                        StatusTesKediri::TUNDA->value
                    ])
            )
            ->description(fn() => 'Rekap Kelulusan Tes Periode '.$periode['monthName'].' '.$periode['year'])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kelompok')
                    ->label('Klp')
                    ->badge()
                    ->sortable(),

                TextColumn::make('nomor_cocard')
                    ->label('No')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('siswa.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->sortable(),

                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('totalPoinAkhlak')
                    ->label('Akhlak')
                    ->badge()
                    ->color(function($record) {
                        if ($record->totalPoinAkhlak > 0 && $record->totalPoinAkhlak <= 30) {
                            return 'info';
                        }
                        if ($record->totalPoinAkhlak > 31 && $record->totalPoinAkhlak <= 60) {
                            return 'warning';
                        }
                        if ($record->totalPoinAkhlak > 61 && $record->totalPoinAkhlak <= 200) {
                            return 'danger';
                        }
                        else return 'gray';
                    }),

                TextColumn::make('avg_nilai_makna')
                    ->label('x̄ Makna')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),

                TextColumn::make('avg_nilai_keterangan')
                    ->label('x̄ Keterangan')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),

                TextColumn::make('avg_nilai_penjelasan')
                    ->label('x̄ Penjelasan')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),

                TextColumn::make('avg_nilai_pemahaman')
                    ->label('x̄ Pemahaman')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),

                TextColumn::make('avg_nilai')
                    ->label('Nilai Akhir')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),

                TextColumn::make('hasil_sistem')
                    ->label('Hasil Sistem')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        HasilSistem::LULUS->getLabel() => 'success',
                        HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => 'danger',
                        HasilSistem::BELUM_PENGETESAN->getLabel() => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status_tes')
                    ->label('Status Tes')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                SelectColumn::make('status_kelanjutan')
                    ->label('Status Kelanjutan')
                    ->options(StatusKelanjutanKediri::class)
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('siswa.nama_lengkap', 'asc')
            ->filters([
                Filter::make('jenis_kelamin')
                    ->form([
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jenis_kelamin'],
                                fn (Builder $query): Builder =>
                                    $query->whereHas('siswa', function ($query) use ($data) {
                                        $query->where('jenis_kelamin', $data['jenis_kelamin']);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['jenis_kelamin']) {
                            return null;
                        }

                        return 'Jenis Kelamin: ' . $data['jenis_kelamin'];
                    }),
                SelectFilter::make('kelompok')
                    ->label('Kelompok')
                    ->options(KelompokKediri::class),
                SelectFilter::make('status_tes')
                    ->label('Status Final')
                    ->options(StatusTesKediri::class),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('sesuaikan_hasil_Sistem')
                    ->label('Sesuaikan Hasil')
                    ->action(function () use ($periode_pengetesan_id): void {
                        DB::table('tes_santri')
                            ->where('periode_id', $periode_pengetesan_id)
                            ->whereNotIn('status_tes', [
                                StatusTesKediri::PRA_TES->value,
                                StatusTesKediri::APPROVED_MATERI->value,
                                StatusTesKediri::APPROVED_BARANG->value,
                                StatusTesKediri::REJECTED_MATERI->value,
                                StatusTesKediri::REJECTED_BARANG->value,
                                StatusTesKediri::TUNDA->value,
                            ])
                            ->update([
                                'status_tes' => DB::raw('
                                    CASE
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id)
                                        ) = 0 THEN status_tes
                                        WHEN (
                                            (SELECT AVG(nilai_makna) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_keterangan) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_penjelasan) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_pemahaman) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id)
                                        ) / 4 >= 70 THEN "'.\App\Enums\HasilTes::LULUS->value.'"
                                        ELSE "'.\App\Enums\HasilTes::TIDAK_LULUS_AKADEMIK->value.'"
                                    END
                                '),
                                'status_kelanjutan' => DB::raw('
                                    CASE
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id)
                                        ) = 0 THEN status_kelanjutan
                                        WHEN (
                                            (SELECT AVG(nilai_makna) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_keterangan) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_penjelasan) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id) +
                                            (SELECT AVG(nilai_pemahaman) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tes_santri.id)
                                        ) / 4 >= 70 THEN "'.\App\Enums\StatusKelanjutanKediri::KERTOSONO->value.'"
                                        ELSE "'.\App\Enums\StatusKelanjutanKediri::LENGKONG->value.'"
                                    END
                                '),
                            ]);
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('reset_hasil_sistem')
                    ->label('Reset Hasil')
                    ->action(function () use ($periode_pengetesan_id): void {
                        PesertaKediri::where('periode_id', $periode_pengetesan_id)
                            ->whereNotIn('status_tes', [
                                StatusTesKediri::PRA_TES->value,
                                StatusTesKediri::APPROVED_BARANG->value,
                                StatusTesKediri::APPROVED_MATERI->value,
                                StatusTesKediri::REJECTED_BARANG->value,
                                StatusTesKediri::REJECTED_MATERI->value,
                                StatusTesKediri::TUNDA->value
                            ])
                            ->update([
                                'status_tes' => StatusTesKediri::AKTIF->value,
                                'status_kelanjutan' => null,
                            ]);
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                ExportAction::make()
                    ->label('Ekspor')
                    ->exporter(HasilTesKediriExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query->with('siswa')
                        ->withHasilSistem()
                        ->where('periode_id', $periode_pengetesan_id)
                    )
            ])
            ->actions([
                Action::make('ubah_status_tes')
                    ->label('Ubah Kelulusan')
                    ->fillForm(function (PesertaKediri $record): array {
                        // Apply the scope to include generated values
                        $record = PesertaKediri::query()
                            ->withHasilSistem()
                            ->find($record->id); // Ensure you're fetching the record with the scope applied

                        return [
                            'nama' => $record->siswa->nama_lengkap.' ('.$record->kelompok.$record->nomor_cocard.')',
                            'akademik' => $record->akademik,
                            'akhlak' => $record->akhlak,
                            'avg_nilai' => $record->avg_nilai, // Access the generated avg_nilai
                            'hasil_sistem' => $record->hasil_sistem, // Access the generated hasil_sistem
                            'status_tes' => $record->status_tes, // Access the generated hasil_sistem
                            'status_kelanjutan' => $record->status_kelanjutan, // Access the generated hasil_sistem
                        ];
                    })
                    ->form([
                        TextInput::make('nama')
                            ->disabled(),
                        TableRepeater::make('akademik')
                            ->label('Daftar Penilaian')
                            ->relationship('akademik')
                            ->headers([
                                Header::make('Guru'),
                                Header::make('Makna'),
                                Header::make('Keterangan'),
                                Header::make('Penjelasan'),
                                Header::make('Pemahaman'),
                                Header::make('Catatan'),
                            ])
                            ->schema([
                                Select::make('guru_id')
                                    ->relationship('guru', 'nama')
                                    ->disabled(),
                                TextInput::make('nilai_makna')
                                    ->disabled(),
                                TextInput::make('nilai_keterangan')
                                    ->disabled(),
                                TextInput::make('nilai_penjelasan')
                                    ->disabled(),
                                TextInput::make('nilai_pemahaman')
                                    ->disabled(),
                                TextInput::make('catatan')
                                    ->disabled(),
                            ])
                            ->deletable(false)
                            ->addable(false),
                        TableRepeater::make('akhlak')
                            ->label('Daftar Akhlak')
                            ->relationship('akhlak')
                            ->headers([
                                Header::make('Guru'),
                                Header::make('Poin'),
                                Header::make('Catatan'),
                            ])
                            ->schema([
                                Select::make('guru_id')
                                    ->relationship('guru', 'nama')
                                    ->disabled(),
                                TextInput::make('poin')
                                    ->disabled(),
                                TextInput::make('catatan')
                                    ->disabled(),
                            ])
                            ->deletable(false)
                            ->addable(false),
                        TextInput::make('avg_nilai')
                            ->label('Rata-Rata Total')
                            ->disabled(),
                        TextInput::make('hasil_sistem')
                            ->label('Hasil Sistem')
                            ->disabled(),
                        ToggleButtons::make('status_tes')
                            ->label('Status Tes')
                            ->options(\App\Enums\HasilTes::class)
                            ->default(null)
                            ->grouped()
                            ->inline()
                            ->required(),
                        ToggleButtons::make('status_kelanjutan')
                             ->label('Status Kelanjutan')
                             ->options(StatusKelanjutanKediri::class)
                             ->default(null)
                             ->grouped()
                             ->inline()
                             ->required()
                    ])
                    ->action(function (array $data, PesertaKediri $record): void {
                        $record->status_tes = $data['status_tes'];
                        $record->status_kelanjutan = $data['status_kelanjutan'];
                        $record->save();
                    }),
                Action::make('ubah_status_kelanjutan')
                    ->label('Ubah Kelanjutan')
                    ->fillForm(function (PesertaKediri $record): array {
                        return [
                            'status_kelanjutan' => $record->status_kelanjutan,
                        ];
                    })
                    ->form([
                        Select::make('status_kelanjutan')
                            ->label('Status Kelanjutan')
                            ->options(StatusKelanjutanKediri::class)
                            ->required()
                    ])
                    ->action(function (array $data, PesertaKediri $record): void {
                        $record->status_kelanjutan = $data['status_kelanjutan'];
                        $record->save();
                    })
            ])
            ->bulkActions([

            ]);
    }
}
