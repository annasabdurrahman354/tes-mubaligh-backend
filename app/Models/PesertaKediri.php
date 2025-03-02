<?php

namespace App\Models;

use App\Enums\HasilSistem;
use App\Enums\StatusKelanjutan;
use App\Enums\StatusKelanjutanKediri;
use App\Enums\StatusTes;
use App\Enums\StatusTesKediri;
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

class PesertaKediri extends Model
{
    use HasFactory;

    protected $table = 'tb_tes_santri';
    protected $primaryKey = 'id_tes_santri';

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
        //'kelompok' => KelompokKediri::class,
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
        $riwayat_tes = PesertaKediri::where('nispn', $this->nispn)
            ->where('periode_id', '<', $this->periode_id)
            ->count();

        return Attribute::make(
            get: fn () => $riwayat_tes,
        );
    }

    protected function totalPoinAkhlak(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->akhlak()->sum('poin')
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
        return $this->hasMany(AkhlakKediri::class, 'tes_santri_id');
    }

    public function akademik()
    {
        return $this->hasMany(AkademikKediri::class, 'tes_santri_id');
    }

    public function scopeWithHasilSistem($query): void
    {
        $query->addSelect([
            'avg_nilai_makna' => AkademikKediri::selectRaw('AVG(nilai_makna)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_keterangan' => AkademikKediri::selectRaw('AVG(nilai_keterangan)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_penjelasan' => AkademikKediri::selectRaw('AVG(nilai_penjelasan)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_pemahaman' => AkademikKediri::selectRaw('AVG(nilai_pemahaman)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai' => AkademikKediri::selectRaw('(
                (AVG(nilai_makna)) +
                (AVG(nilai_keterangan)) +
                (AVG(nilai_penjelasan)) +
                (AVG(nilai_pemahaman))
            ) / 4')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'hasil_sistem' => AkademikKediri::selectRaw("CASE
                WHEN (SELECT COUNT(*) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tb_tes_santri.id_tes_santri) = 0
                    THEN '".HasilSistem::BELUM_PENGETESAN->getLabel()."'
                WHEN (
                    (AVG(nilai_makna) +
                    AVG(nilai_keterangan) +
                    AVG(nilai_penjelasan) +
                    AVG(nilai_pemahaman)) / 4
                ) >= 70
                    THEN '".HasilSistem::LULUS->getLabel()."'
                ELSE '".HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()."'
            END")
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),
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

            TextColumn::make('kelompok')
                ->label('Klp')
                ->badge()
                ->sortable(),

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
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                            return $rule
                                ->where('periode_id', $get('periode_id'))
                                ->where('nispn', $state)
                                ->where('tahap', Tahap::KEDIRI->value);
                        })
                        ->validationMessages([
                            'unique' => 'Santri sudah terdaftar tes kediri di periode tersebut.',
                        ]),
                    TextInput::make('kelompok')
                        ->label('Kelompok'),
                    TextInput::make('nomor_cocard')
                        ->label('Nomor Cocard')
                        ->numeric(),
                    Select::make('ponpes_id')
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
                        ->options(StatusTesKediri::class)
                        ->default(StatusTesKediri::PRA_TES)
                        ->required(fn(Get $get) => $get('status_tes'))
                        ->live(),

                    ToggleButtons::make('status_kelanjutan')
                        ->label('Kelanjutan')
                        ->inline()
                        ->options(function (Get $get) {
                            if ($get('status_tes') == StatusTesKediri::LULUS) {
                                return[
                                    StatusKelanjutanKediri::KERTOSONO->value => StatusKelanjutanKediri::KERTOSONO->value,
                                    StatusKelanjutanKediri::PONDOK_ASAL->value => StatusKelanjutanKediri::PONDOK_ASAL->value,
                                ];
                            }
                            elseif (in_array($get('status_tes'), [StatusTesKediri::TIDAK_LULUS_AKHLAK, StatusTesKediri::TIDAK_LULUS_AKADEMIK])) {
                                return[
                                    StatusKelanjutanKediri::PONDOK_ASAL->value => StatusKelanjutanKediri::PONDOK_ASAL->value,
                                    StatusKelanjutanKediri::LENGKONG->value => StatusKelanjutanKediri::LENGKONG->value,
                                ];
                            }
                        })
                        ->required(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKediri::LULUS, StatusTesKediri::TIDAK_LULUS_AKHLAK, StatusTesKediri::TIDAK_LULUS_AKADEMIK])
                        )
                        ->visible(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKediri::LULUS, StatusTesKediri::TIDAK_LULUS_AKHLAK, StatusTesKediri::TIDAK_LULUS_AKADEMIK])
                        ),
                ]),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tahap', function ($builder) {
            $builder->where('tahap', Tahap::KEDIRI->value);
        });
    }
}
