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
use Illuminate\Database\Eloquent\Builder;

class PesertaKertosono extends Model
{
    use HasFactory;

    protected $table = 'tb_tes_santri';
    protected $primaryKey = 'id_tes_santri';

    protected $fillable = [
        'id_ponpes',
        'id_periode',
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
            get: fn () => $this->siswa->nama_lengkap.' ('.$this->id_periode.')',
        );
    }

    protected function riwayatTes(): Attribute
    {
        $riwayat_tes = PesertaKertosono::where('nispn', $this->nispn)
            ->where('id_periode', '<', $this->id_periode)
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
            get: fn () => $this->ponpes->n_ponpes." (".$this->ponpes->daerah->n_daerah.")"
        );
    }

    public function rekomendasiGuru(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->akademik()
                ->where('rekomendasi_penarikan', 1)
                ->with('guru')
                ->get()
                ->pluck('guru.nama')
                ->unique()
                ->implode(', ')
        );
    }

    protected function allKekurangan(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Collect and merge all kekurangan fields
                $allKekurangan = $this->akademik->flatMap(function ($record) {
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

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'nispn', 'nispn');
    }

    public function ponpes()
    {
        return $this->belongsTo(Ponpes::class, 'id_ponpes', 'id_ponpes');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }

    public function akhlak()
    {
        return $this->hasMany(AkhlakKertosono::class, 'tes_santri_id');
    }

    public function akademik()
    {
        return $this->hasMany(AkademikKertosono::class, 'tes_santri_id');
    }

    public function scopeWithHasilSistem($query): void
    {
        $query->addSelect([
            'count_lulus' => AkademikKertosono::selectRaw('COUNT(*)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri')
                ->where('penilaian', PenilaianKertosono::LULUS->value),

            'count_tidak_lulus' => AkademikKertosono::selectRaw('COUNT(*)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri')
                ->where('penilaian', PenilaianKertosono::TIDAK_LULUS->value),

            'hasil_sistem' => AkademikKertosono::selectRaw("
            CASE
                WHEN SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) >
                     SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)
                THEN ?
                WHEN SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) <
                     SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)
                THEN ?
                WHEN SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) =
                     SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)
                     AND SUM(CASE WHEN penilaian IN (?, ?) THEN 1 ELSE 0 END) > 0
                THEN ?
                ELSE ?
            END", [
                PenilaianKertosono::LULUS->value,  // First condition
                PenilaianKertosono::TIDAK_LULUS->value,
                HasilSistem::LULUS->getLabel(),

                PenilaianKertosono::LULUS->value,  // Second condition
                PenilaianKertosono::TIDAK_LULUS->value,
                HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel(),

                PenilaianKertosono::LULUS->value,  // Third condition
                PenilaianKertosono::TIDAK_LULUS->value,
                PenilaianKertosono::LULUS->value,
                PenilaianKertosono::TIDAK_LULUS->value,
                HasilSistem::PERLU_MUSYAWARAH->getLabel(),

                HasilSistem::BELUM_PENGETESAN->getLabel() // Default case
            ])
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),
        ]);
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id_tes_santri')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('id_periode')
                ->label('Periode')
                ->sortable()
                ->searchable(),

            TextColumn::make('nomor_cocard')
                ->label('No')
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->orderByRaw('CONVERT(nomor_cocard, SIGNED) '.$direction);
                }),

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

            //TextColumn::make('created_at')
            //    ->label('Created At')
            //    ->dateTime()
            //    ->sortable()
            //    ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getForm()
    {
        return [
            Section::make('Peserta')
                ->description('Informasi terkait peserta tes.')
                ->schema([
                    Select::make('id_periode')
                        ->label('Periode')
                        ->relationship('periode', 'id_periode')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('nispn')
                        ->label('Siswa')
                        ->relationship('siswa', 'nama_lengkap')
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                            return $rule
                                ->where('id_periode', $get('id_periode'))
                                ->where('nispn', $state)
                                ->where('tahap', Tahap::KERTOSONO->value);
                        })
                        ->validationMessages([
                            'unique' => 'Santri sudah terdaftar tes kediri di periode tersebut.',
                        ]),
                    TextInput::make('nomor_cocard')
                        ->label('Nomor Cocard')
                        ->numeric(),
                    Select::make('id_ponpes')
                        ->label('Asal Pondok')
                        ->relationship('ponpes', 'n_ponpes')
                        ->preload()
                        ->searchable()
                        ->required(),
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
        static::addGlobalScope('del_status', function ($builder) {
            $builder->where('del_status', NULL);
        });
    }
}
