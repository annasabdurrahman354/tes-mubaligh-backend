<?php

namespace App\Filament\Pages;

use App\Enums\HasilSistem;
use App\Enums\JenisKelamin;
use App\Enums\PenilaianKertosono;
use App\Enums\StatusKelanjutanKertosono;
use App\Enums\StatusTesKertosono;
use App\Models\PesertaKertosono;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
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

class HasilTesKertosono extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $slug = 'hasil-tes-kertosono';
    protected static ?string $title = 'Hasil Tes Kertosono';
    protected static ?string $navigationLabel = 'Hasil Tes';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.hasil-tes-kertosono';

    public function table(Table $table): Table
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        return $table
            ->query(
                PesertaKertosono::query()
                    ->with('siswa')
                    ->withHasilSistem()
                    ->where('id_periode', $periode_pengetesan_id)
                    ->whereNotIn('status_tes', [
                        StatusTesKertosono::TUNDA->value
                    ])
            )
            ->description(fn() => 'Rekap Kelulusan Tes Periode '.$periode['monthName'].' '.$periode['year'])
            ->columns([
                TextColumn::make('id_tes_santri')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nomor_cocard')
                    ->label('No')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw('CONVERT(nomor_cocard, SIGNED) '.$direction);
                    }),

                TextColumn::make('siswa.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->sortable(),

                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('count_lulus')
                    ->label('Lulus')
                    ->sortable(),

                TextColumn::make('count_tidak_lulus')
                    ->label('Tidak Lulus')
                    ->sortable(),

                TextColumn::make('akademik_count')
                    ->label('Direkomendasikan')
                    ->counts(['akademik' => fn ($query) => $query->where('rekomendasi_penarikan', true)])
                    ->formatStateUsing(function (PesertaKertosono $record) {
                        return $record->rekomendasi_guru;
                    })
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->sortable(),

                TextColumn::make('hasil_sistem')
                    ->label('Hasil Sistem')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        HasilSistem::LULUS->getLabel() => 'success',
                        HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => 'danger',
                        HasilSistem::PERLU_MUSYAWARAH->getLabel() => 'info',
                        HasilSistem::BELUM_PENGETESAN->getLabel() => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status_tes')
                    ->label('Status Tes')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                SelectColumn::make('status_kelanjutan')
                    ->label('Status Kelajutan')
                    ->options(StatusKelanjutanKertosono::class)
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('nomor_cocard', 'asc')
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
                SelectFilter::make('status_tes')
                    ->label('Status Tes')
                    ->options(StatusTesKertosono::class),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('ubah_status_tes')
                    ->label('Ubah Kelulusan')
                    ->fillForm(function (PesertaKertosono $record): array {
                        return [
                            'nama' => $record->siswa->nama_lengkap . ' (' . $record->kelompok . $record->nomor_cocard . ')',
                            'akademik' => $record->akademik,
                            'akhlak' => $record->akhlak,
                            'hasil_sistem' => $record->hasil_sistem, // Access the generated hasil_sistem
                            'allKekurangan' => $record->allKekurangan,
                            'status_tes' => $record->status_tes,
                            'status_kelanjutan' => $record->status_kelanjutan
                        ];
                    })
                    ->form([
                        TextInput::make('nama')
                            ->disabled(),
                        TableRepeater::make('akademik')
                            ->label('Daftar Penilaian')
                            ->relationship('akademik')
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                $data['allKekurangan'] = array_merge(
                                    $data['kekurangan_tajwid'] ?? [],
                                        $data['kekurangan_khusus'] ?? [],
                                    $data['kekurangan_keserasian'] ?? [],
                                    $data['kekurangan_kelancaran'] ?? [],
                                );

                                return $data;
                            })
                            ->headers([
                                Header::make('Guru'),
                                Header::make('Penilaian'),
                                Header::make('Kekurangan'),
                                Header::make('Catatan'),
                                Header::make('Lama'),
                            ])
                            ->schema([
                                Select::make('guru_id')
                                    ->relationship('guru', 'nama')
                                    ->disabled(),
                                TextInput::make('penilaian')
                                    ->disabled(),
                                TagsInput::make('allKekurangan')
                                    ->disabled(),
                                TextInput::make('catatan')
                                    ->disabled(),
                                TextInput::make('durasi_penilaian')
                                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' menit')
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
                        TagsInput::make('allKekurangan')
                            ->label('Semua Kekurangan')
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
                        Select::make('status_kelanjutan')
                            ->label('Status Kelanjutan')
                            ->options(StatusKelanjutanKertosono::class)
                            ->required()
                    ])
                    ->action(function (array $data, PesertaKertosono $record): void {
                        $record->status_tes = $data['status_tes'];
                        $record->status_kelanjutan = $data['status_kelanjutan'];
                        $record->save();
                    }),
                Action::make('ubah_status_kelanjutan')
                    ->label('Ubah Kelanjutan')
                    ->fillForm(function (PesertaKertosono $record): array {
                        return [
                            'status_kelanjutan' => $record->status_kelanjutan,
                        ];
                    })
                    ->form([
                        Select::make('status_kelanjutan')
                            ->label('Status Kelanjutan')
                            ->options(StatusKelanjutanKertosono::class)
                            ->required()
                    ])
                    ->action(function (array $data, PesertaKertosono $record): void {
                        $record->status_kelanjutan = $data['status_kelanjutan'];
                        $record->save();
                    })
            ])
            ->headerActions([
                Action::make('sesuaikan_hasil_sistem')
                    ->label('Sesuaikan Hasil')
                    ->action(function () use ($periode_pengetesan_id): void {
                        PesertaKertosono::where('id_periode', $periode_pengetesan_id)
                            ->whereNotIn('status_tes', [
                                StatusTesKertosono::PRA_TES->value,
                                StatusTesKertosono::TUNDA->value,
                            ])
                            ->withHasilSistem() // Leverage the scope to get computed columns
                            ->update([
                                'status_tes' => DB::raw("
                                    CASE
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kertosono
                                             WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                             AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::LULUS->value . "')
                                        ) > (
                                            SELECT COUNT(*) FROM tes_akademik_kertosono
                                            WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                            AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::TIDAK_LULUS->value . "'
                                        )
                                        THEN '" . \App\Enums\HasilTes::LULUS->value . "'
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kertosono
                                             WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                             AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::TIDAK_LULUS->value . "')
                                        ) > (
                                            SELECT COUNT(*) FROM tes_akademik_kertosono
                                            WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                            AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::LULUS->value . "'
                                        )
                                        THEN '" . \App\Enums\HasilTes::TIDAK_LULUS_AKADEMIK->value . "'
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kertosono
                                             WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                             AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::TIDAK_LULUS->value . "')
                                        ) = (
                                            SELECT COUNT(*) FROM tes_akademik_kertosono
                                            WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                            AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::LULUS->value . "'
                                        )
                                        THEN '" . \App\Enums\HasilTes::PERLU_MUSYAWARAH->value . "'
                                        ELSE status_tes
                                    END
                                "),
                                    'status_kelanjutan' => DB::raw("
                                    CASE
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kertosono
                                             WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                             AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::LULUS->value . "')
                                        ) > (
                                            SELECT COUNT(*) FROM tes_akademik_kertosono
                                            WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                            AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::TIDAK_LULUS->value . "'
                                        )
                                        THEN '" . \App\Enums\StatusKelanjutanKertosono::KEDIRI->value . "'
                                        WHEN (
                                            (SELECT COUNT(*) FROM tes_akademik_kertosono
                                             WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                             AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::TIDAK_LULUS->value . "')
                                        ) > (
                                            SELECT COUNT(*) FROM tes_akademik_kertosono
                                            WHERE tes_akademik_kertosono.tes_santri_id = tb_tes_santri.id_tes_santri
                                            AND tes_akademik_kertosono.penilaian = '" . PenilaianKertosono::LULUS->value . "'
                                        )
                                        THEN '" . \App\Enums\StatusKelanjutanKertosono::LENGKONG->value . "'
                                        ELSE status_kelanjutan
                                    END
                                "),
                            ]);
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
                Action::make('reset_hasil_sistem')
                    ->label('Reset Hasil')
                    ->action(function () use ($periode_pengetesan_id): void {
                        PesertaKertosono::where('id_periode', $periode_pengetesan_id)
                            ->whereNotIn('status_tes', [
                                StatusTesKertosono::TUNDA->value,
                                StatusTesKertosono::PRA_TES->value,
                            ])
                            ->update([
                                'status_tes' => StatusTesKertosono::AKTIF->value,
                                'status_kelanjutan' => null,
                            ]);
                    })
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([

            ])
            ->striped();
    }

}
