<?php

namespace App\Models;

use App\Enums\HasilSistem;
use App\Enums\PenilaianKertosono;
use App\Enums\StatusKelanjutan;
use App\Enums\StatusKelanjutanKertosono;
use App\Enums\StatusTes;
use App\Enums\StatusTesKertosono;
use App\Enums\Tahap;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class PesertaKertosono extends Model
{
    use HasFactory;

    protected $table = 'tes_santri';

    protected $fillable = [
        'ponpes_id',
        'periode_id',
        'nispn',
        'tahap',
        'kelompok',
        'nomor_cocard',
        'status_tes',
        'status_kelanjutan',
    ];

    protected $casts = [
        'nomor_cocard' => 'integer',
        'tahap' => Tahap::class,
        'status_tes' => StatusTes::class,
        'status_kelanjutan' => StatusKelanjutan::class,
    ];

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->siswa->nama_lengkap.' ('.$this->periode_id.')',
        );
    }

    protected function riwayatTes(): Attribute
    {
        $riwayat_tes = PesertaKertosono::where('nispn', $this->nispn)
            ->where('periode_id', '<', $this->periode_id)
            ->count();

        return Attribute::make(
            get: fn () => $riwayat_tes,
        );
    }

    protected function asalDaerahNama(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->siswa->daerahSambung->n_daerah
        );
    }

    protected function asalPondokNama(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ponpes->n_ponpes
        );
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'nispn', 'nispn');
    }

    public function ponpes()
    {
        return $this->belongsTo(Ponpes::class, 'ponpes_id', 'id_ponpes');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id', 'id_periode');
    }

    public function akhlak()
    {
        return $this->hasMany(AkhlakKertosono::class, 'tes_santri_id');
    }

    public function akademik()
    {
        return $this->hasMany(AkademikKertosono::class, 'tes_santri_id');
    }

    protected function allKekurangan(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Fetch all related akademik_kertosono records
                $relatedRecords = $this->akademik;

                // Collect and merge all kekurangan fields
                $allKekurangan = $relatedRecords->flatMap(function ($record) {
                    return array_merge(
                        $record->kekurangan_tajwid ?? [],
                        $record->kekurangan_khusus ?? [],
                        $record->kekurangan_keserasian ?? [],
                        $record->kekurangan_kelancaran ?? []
                    );
                });

                // Remove duplicates and return as an array
                return array_values(array_unique($allKekurangan->toArray()));
            }
        );
    }

    public function scopeWithHasilSistem($query): void
    {
        $query->addSelect([
            'count_lulus' => AkademikKertosono::selectRaw('COUNT(*)')
                ->whereColumn('tes_santri_id', 'tes_santri.id')
                ->where('penilaian', PenilaianKertosono::LULUS->value),

            'count_tidak_lulus' => AkademikKertosono::selectRaw('COUNT(*)')
                ->whereColumn('tes_santri_id', 'tes_santri.id')
                ->where('penilaian', PenilaianKertosono::TIDAK_LULUS->value),

            'hasil_sistem' => AkademikKertosono::selectRaw("
                CASE
                    WHEN (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::LULUS->value."')
                        >
                        (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::TIDAK_LULUS->value."')
                    THEN '".HasilSistem::LULUS->getLabel()."'
                    WHEN (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::TIDAK_LULUS->value."')
                        >
                        (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::LULUS->value."')
                    THEN '".HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()."'
                    WHEN (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::TIDAK_LULUS->value."')
                        =
                        (SELECT COUNT(*) FROM tes_akademik_kertosono WHERE tes_akademik_kertosono.tes_santri_id = tes_santri.id AND tes_akademik_kertosono.penilaian = '".PenilaianKertosono::LULUS->value."')
                    THEN '".HasilSistem::PERLU_MUSYAWARAH->getLabel()."'
                    ELSE '".HasilSistem::BELUM_PENGETESAN->getLabel()."'
                END
            "),
        ]);
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('periode_id')
                ->label('Periode')
                ->sortable()
                ->searchable(),

            TextColumn::make('nomor_cocard')
                ->label('No')
                ->sortable(),

            TextColumn::make('siswa.nama_lengkap')
                ->label('Nama')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.jenis_kelamin')
                ->label('Jenis Kelamin')
                ->badge()
                ->sortable()
                ->searchable(),

            TextColumn::make('ponpes.n_ponpes')
                ->label('Asal Pondok')
                ->sortable()
                ->searchable(),

            TextColumn::make('riwayat_tes')
                ->label('Riwayat Tes')
                ->sortable(),

            TextColumn::make('siswa.nik')
                ->label('NIK')
                ->sortable()
                ->searchable(),

            TextColumn::make('nispn')
                ->label('NISPN')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.rfid')
                ->label('RFID')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.hp')
                ->label('Nomor Telepon')
                ->searchable(),

            TextColumn::make('siswa.email')
                ->label('Email')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.tempat_lahir')
                ->label('Tempat Lahir')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.tanggal_lahir')
                ->label('Tanggal Lahir')
                ->date()
                ->sortable(),

            TextColumn::make('siswa.umur')
                ->label('Umur'),

            TextColumn::make('siswa.alamat')
                ->label('Alamat')
                ->limit(50)
                ->sortable()
                ->searchable(),

            //TODO: PROVINSI AND KOTA

            TextColumn::make('siswa.daerahSambung.n_daerah')
                ->label('Asal Daerah')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.desa_sambung')
                ->label('Asal Desa')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.kelompok_sambung')
                ->label('Asal Kelompok')
                ->sortable()
                ->searchable(),

            TextColumn::make('siswa.status_mondok')
                ->label('Status Mondok')
                ->badge()
                ->sortable()
                ->searchable(),

            TextColumn::make('status_tes')
                ->label('Status Tes')
                ->badge()
                ->sortable()
                ->searchable(),

            TextColumn::make('status_kelanjutan')
                ->label('Status Kelanjutan')
                ->badge()
                ->sortable()
                ->searchable(),

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getForm()
    {
        return [
            Section::make('Peserta')
                ->description('Informasi terkait peserta tes.')
                ->schema([
                    Select::make('periode_id')
                        ->label('Periode')
                        ->relationship('periode', 'id_periode')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('nispn')
                        ->label('Siswa')
                        ->relationship('siswa', 'nama_lengkap')
                        ->searchable()
                        ->required(),
                    TextInput::make('nomor_cocard')
                        ->label('Nomor Cocard')
                        ->numeric(),
                ]),

            Section::make('Status Tes')
                ->description('Informasi terkait status peserta tes.')
                ->schema([
                    Select::make('status_tes')
                        ->label('Status Tes')
                        ->options(StatusTesKertosono::class)
                        ->default(StatusTesKertosono::AKTIF)
                        ->live(),

                    ToggleButtons::make('status_kelanjutan')
                        ->label('Kelanjutan')
                        ->grouped()
                        ->inline()
                        ->options(function (Get $get) {
                            if ($get('status_tes') == StatusTesKertosono::LULUS) {
                                return [
                                    StatusKelanjutanKertosono::KEDIRI->value => StatusKelanjutanKertosono::KEDIRI->value,
                                ];
                            }
                            elseif (in_array($get('status_tes'), [StatusTesKertosono::TIDAK_LULUS_AKHLAK, StatusTesKertosono::TIDAK_LULUS_AKADEMIK])) {
                                return [
                                    StatusKelanjutanKertosono::PONDOK_ASAL->value => StatusKelanjutanKertosono::PONDOK_ASAL->value,
                                    StatusKelanjutanKertosono::LENGKONG->value => StatusKelanjutanKertosono::LENGKONG->value,
                                    StatusKelanjutanKertosono::KERTOSONO->value => StatusKelanjutanKertosono::KERTOSONO->value,
                                ];
                            }
                        })
                        ->required(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKertosono::LULUS, StatusTesKertosono::TIDAK_LULUS_AKHLAK, StatusTesKertosono::TIDAK_LULUS_AKADEMIK])
                        )
                        ->visible(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKertosono::LULUS, StatusTesKertosono::TIDAK_LULUS_AKHLAK, StatusTesKertosono::TIDAK_LULUS_AKADEMIK])
                        ),
                ]),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tahap', function ($builder) {
            $builder->where('tahap', Tahap::KERTOSONO->value);
        });
    }
}
