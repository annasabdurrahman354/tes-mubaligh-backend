<?php

namespace App\Models;

use App\Enums\HasilSistem;
use App\Enums\PenilaianKertosono;
use App\Enums\StatusTesKediri;
use App\Enums\StatusTesKertosono;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables;

class Ponpes extends Model
{
    use HasFactory;

    protected $table = 'tb_ponpes';
    protected $primaryKey = 'id_ponpes';
    public $timestamps = false;

    protected $fillable = [
        'id_daerah',
        'n_ponpes',
        'status',
    ];

    public function daerah()
    {
        return $this->belongsTo(Daerah::class, 'id_daerah', 'id_daerah');
    }

    public function pesertaKediri()
    {
        return $this->hasMany(PesertaKediri::class, 'id_ponpes', 'id_ponpes');
    }

    public function pesertaKertosono()
    {
        return $this->hasMany(PesertaKertosono::class, 'id_ponpes', 'id_ponpes');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'id_ponpes', 'id_ponpes');
    }

    public function scopeWithPesertaKediriHasilSistemCounts($query)
    {
        $query->addSelect([
            'peserta_total' => PesertaKediri::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->where('id_periode', getPeriodeTes()),

            'peserta_lulus' => PesertaKediri::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->whereHas('akademik')
                ->whereIn('id', function ($subquery) {
                    $subquery->select('tb_tes_santri.id_tes_santri')
                        ->from('tes_santri')
                        ->leftJoin('tes_akademik_kediri', 'tes_akademik_kediri.tes_santri_id', '=', 'tb_tes_santri.id_tes_santri')
                        ->whereColumn('tb_tes_santri.id_ponpes', 'tb_ponpes.id_ponpes')
                        ->groupBy('tb_tes_santri.id_tes_santri')
                        ->havingRaw("
                        CASE
                            WHEN COUNT(tes_akademik_kediri.id) = 0 THEN ?
                            WHEN AVG((nilai_makna + nilai_keterangan + nilai_penjelasan + nilai_pemahaman) / 4) >= 70 THEN ?
                            ELSE ?
                        END = ?", [
                            HasilSistem::BELUM_PENGETESAN->getLabel(),
                            HasilSistem::LULUS->getLabel(),
                            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel(),
                            HasilSistem::LULUS->getLabel()
                        ]);
                }),

            'peserta_tidak_lulus' => PesertaKediri::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->whereHas('akademik')
                ->whereIn('id', function ($subquery) {
                    $subquery->select('tb_tes_santri.id_tes_santri')
                        ->from('tes_santri')
                        ->leftJoin('tes_akademik_kediri', 'tes_akademik_kediri.tes_santri_id', '=', 'tb_tes_santri.id_tes_santri')
                        ->whereColumn('tb_tes_santri.id_ponpes', 'tb_ponpes.id_ponpes')
                        ->groupBy('tb_tes_santri.id_tes_santri')
                        ->havingRaw("
                        CASE
                            WHEN COUNT(tes_akademik_kediri.id) = 0 THEN ?
                            WHEN AVG((nilai_makna + nilai_keterangan + nilai_penjelasan + nilai_pemahaman) / 4) >= 70 THEN ?
                            ELSE ?
                        END = ?", [
                            HasilSistem::BELUM_PENGETESAN->getLabel(),
                            HasilSistem::LULUS->getLabel(),
                            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel(),
                            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()
                        ]);
                }),
        ]);
    }

    public function scopeWithPesertaKertosonoHasilSistemCounts($query)
    {
        $query->addSelect([
            'peserta_total' => PesertaKertosono::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->where('id_periode', getPeriodeTes()),

            'peserta_lulus' => PesertaKertosono::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->whereHas('akademik')
                ->whereIn('id', function ($subquery) {
                    $subquery->select('tb_tes_santri.id_tes_santri')
                        ->from('tes_santri')
                        ->leftJoin('tes_akademik_kertosono', 'tes_akademik_kertosono.tes_santri_id', '=', 'tb_tes_santri.id_tes_santri')
                        ->whereColumn('tb_tes_santri.id_ponpes', 'tb_ponpes.id_ponpes')
                        ->groupBy('tb_tes_santri.id_tes_santri')
                        ->havingRaw('SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) >
                                 SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)', [
                            PenilaianKertosono::LULUS->value,
                            PenilaianKertosono::TIDAK_LULUS->value
                        ]);
                }),

            'peserta_tidak_lulus' => PesertaKertosono::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->whereHas('akademik')
                ->whereIn('id', function ($subquery) {
                    $subquery->select('tb_tes_santri.id_tes_santri')
                        ->from('tes_santri')
                        ->leftJoin('tes_akademik_kertosono', 'tes_akademik_kertosono.tes_santri_id', '=', 'tb_tes_santri.id_tes_santri')
                        ->whereColumn('tb_tes_santri.id_ponpes', 'tb_ponpes.id_ponpes')
                        ->groupBy('tb_tes_santri.id_tes_santri')
                        ->havingRaw('SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) <=
                                 SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)', [
                            PenilaianKertosono::LULUS->value,
                            PenilaianKertosono::TIDAK_LULUS->value
                        ]);
                }),

            'peserta_perlu_musyawarah' => PesertaKertosono::selectRaw('COUNT(*)')
                ->whereColumn('id_ponpes', 'tb_ponpes.id_ponpes')
                ->whereHas('akademik')
                ->whereIn('id', function ($subquery) {
                    $subquery->select('tb_tes_santri.id_tes_santri')
                        ->from('tes_santri')
                        ->leftJoin('tes_akademik_kertosono', 'tes_akademik_kertosono.tes_santri_id', '=', 'tb_tes_santri.id_tes_santri')
                        ->whereColumn('tb_tes_santri.id_ponpes', 'tb_ponpes.id_ponpes')
                        ->groupBy('tb_tes_santri.id_tes_santri')
                        ->havingRaw('SUM(CASE WHEN penilaian IN (?, ?) THEN 1 ELSE 0 END) > 0
                                 AND SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END) =
                                     SUM(CASE WHEN penilaian = ? THEN 1 ELSE 0 END)', [
                            PenilaianKertosono::LULUS->value,
                            PenilaianKertosono::TIDAK_LULUS->value,
                            PenilaianKertosono::LULUS->value,
                            PenilaianKertosono::TIDAK_LULUS->value
                        ]);
                }),
        ]);
    }

    public function scopeWithStatusTesPesertaKediri(Builder $query): void
    {
        $periodeId = getPeriodeTes();

        $query->withCount([
            'pesertaKediri as total_peserta' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId);
            },

            // Count each status_tes value separately
            'pesertaKediri as count_pra_tes' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::PRA_TES);
            },
            'pesertaKediri as count_approved_barang' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::APPROVED_BARANG);
            },
            'pesertaKediri as count_rejected_barang' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::REJECTED_BARANG);
            },
            'pesertaKediri as count_approved_materi' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::APPROVED_MATERI);
            },
            'pesertaKediri as count_rejected_materi' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::REJECTED_MATERI);
            },
            'pesertaKediri as count_tunda' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::TUNDA);
            },
            'pesertaKediri as count_aktif' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::AKTIF);
            },
            'pesertaKediri as count_lulus' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::LULUS);
            },
            'pesertaKediri as count_tidak_lulus_akhlak' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::TIDAK_LULUS_AKHLAK);
            },
            'pesertaKediri as count_tidak_lulus_akademik' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)->where('status_tes', StatusTesKediri::TIDAK_LULUS_AKADEMIK);
            },
        ]);
    }

    public function scopeWithStatusTesPesertaKertosono(Builder $query): void
    {
        $periodeId = getPeriodeTes();

        $query->withCount([
            'pesertaKertosono as total_peserta' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId);
            },

            // Counting Male and Female participants per status
            'pesertaKertosono as count_pra_tes_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::PRA_TES)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_pra_tes_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::PRA_TES)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_tunda_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::TUNDA)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_tunda_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::TUNDA)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_aktif_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::AKTIF)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_aktif_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::AKTIF)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_lulus_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::LULUS)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_lulus_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::LULUS)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_tidak_lulus_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where(function ($q) {
                        $q->where('status_tes', StatusTesKertosono::TIDAK_LULUS_AKHLAK)
                            ->orWhere('status_tes', StatusTesKertosono::TIDAK_LULUS_AKADEMIK);
                    })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_tidak_lulus_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where(function ($q) {
                        $q->where('status_tes', StatusTesKertosono::TIDAK_LULUS_AKHLAK)
                            ->orWhere('status_tes', StatusTesKertosono::TIDAK_LULUS_AKADEMIK);
                    })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_perlu_musyawarah_male' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::PERLU_MUSYAWARAH)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_perlu_musyawarah_female' => function ($query) use ($periodeId) {
                $query->where('id_periode', $periodeId)
                    ->where('status_tes', StatusTesKertosono::PERLU_MUSYAWARAH)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },
        ]);
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Select::make('id_daerah')
                ->label('Daerah')
                ->relationship('daerah', 'n_daerah')
                ->preload()
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('n_ponpes')
                ->label('Nama Ponpes')
                ->required()
                ->maxLength(100),

            Forms\Components\ToggleButtons::make('status')
                ->label('Status')
                ->options([
                    null => '-',
                    'boarding' => "Boarding",
                    'ppm' => "PPM",
                    'pppm' => "PPPM",
                    'pusat' => "Pusat",
                    'reguler' => "Reguler",
                ]),
        ];
    }

    public static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id_ponpes')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('daerah.n_daerah')
                ->label('Nama Daerah')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('n_ponpes')
                ->label('Nama Ponpes')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->sortable()
                ->searchable(),
        ];
    }
}
