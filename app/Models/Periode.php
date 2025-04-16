<?php

namespace App\Models;

use App\Enums\BulanNomor;
use App\Enums\StatusPeriode;
use App\Enums\StatusTesKediri;
use App\Enums\StatusTesKertosono;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class Periode extends Model
{
    use HasFactory;

    protected $table = 'tb_periode';

    protected $primaryKey = 'id_periode';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_periode',
        'bulan',
        'tahun',
        'status',
    ];

    protected $casts = [
        'bulan' => BulanNomor::class,
    ];

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bulan.' '.$this->tahun,
        );
    }

    public function scopeWithStatusTesPesertaKediri(Builder $query, $id_ponpes = null): void
    {
        $periodeId = getPeriodeTes();

        $baseQuery = function ($query) use ($periodeId, $id_ponpes) {
            $query->where('id_periode', $periodeId);
            if ($id_ponpes !== null) {
                $query->where('id_ponpes', $id_ponpes);
            }
        };

        $query->withCount([
            'pesertaKediri as total_peserta' => $baseQuery,

            // Counting Male and Female participants per status
            'pesertaKediri as count_pra_tes_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::PRA_TES->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_pra_tes_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::PRA_TES->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_rejected_barang_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::REJECTED_BARANG->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_rejected_barang_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::REJECTED_BARANG->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_rejected_materi_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::REJECTED_MATERI->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_rejected_materi_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::REJECTED_MATERI->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_tunda_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::TUNDA->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_tunda_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::TUNDA->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_aktif_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::AKTIF->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_aktif_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::AKTIF->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_lulus_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::LULUS->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_lulus_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKediri::LULUS->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKediri as count_tidak_lulus_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where(function ($q) {
                    $q->where('status_tes', StatusTesKediri::TIDAK_LULUS_AKHLAK->value)
                        ->orWhere('status_tes', StatusTesKediri::TIDAK_LULUS_AKADEMIK->value);
                })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKediri as count_tidak_lulus_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where(function ($q) {
                    $q->where('status_tes', StatusTesKediri::TIDAK_LULUS_AKHLAK->value)
                        ->orWhere('status_tes', StatusTesKediri::TIDAK_LULUS_AKADEMIK->value);
                })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },
        ]);
    }

    public function scopeWithStatusTesPesertaKertosono(Builder $query, $id_ponpes = null): void
    {
        $periodeId = getPeriodeTes();

        $baseQuery = function ($query) use ($periodeId, $id_ponpes) {
            $query->where('id_periode', $periodeId);
            if ($id_ponpes !== null) {
                $query->where('id_ponpes', $id_ponpes);
            }
        };

        $query->withCount([
            'pesertaKertosono as total_peserta' => $baseQuery,

            // Counting Male and Female participants per status
            'pesertaKertosono as count_pra_tes_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::PRA_TES->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_pra_tes_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::PRA_TES->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_tunda_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::TUNDA->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_tunda_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::TUNDA->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_aktif_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::AKTIF->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_aktif_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::AKTIF->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_lulus_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::LULUS->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_lulus_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::LULUS->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_tidak_lulus_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where(function ($q) {
                    $q->where('status_tes', StatusTesKertosono::TIDAK_LULUS_AKHLAK->value)
                        ->orWhere('status_tes', StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value);
                })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_tidak_lulus_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where(function ($q) {
                    $q->where('status_tes', StatusTesKertosono::TIDAK_LULUS_AKHLAK->value)
                        ->orWhere('status_tes', StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value);
                })
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },

            'pesertaKertosono as count_perlu_musyawarah_male' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::PERLU_MUSYAWARAH->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'L');
                    });
            },
            'pesertaKertosono as count_perlu_musyawarah_female' => function ($query) use ($baseQuery) {
                $baseQuery($query);
                $query->where('status_tes', StatusTesKertosono::PERLU_MUSYAWARAH->value)
                    ->whereHas('siswa', function ($q) {
                        $q->where('jenis_kelamin', 'P');
                    });
            },
        ]);
    }

    public function pesertaKediri()
    {
        return $this->hasMany(PesertaKediri::class, 'id_periode', 'id_periode');
    }

    public function pesertaKertosono()
    {
        return $this->hasMany(PesertaKertosono::class, 'id_periode', 'id_periode');
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id_periode')
                ->label('ID')
                ->searchable()
                ->sortable(),
            TextColumn::make('bulan')
                ->label('Bulan')
                ->badge()
                ->searchable()
                ->sortable(),
            TextColumn::make('tahun')
                ->label('Tahun')
                ->searchable()
                ->sortable(),
            SelectColumn::make('status')
                ->label('Status')
                ->options(StatusPeriode::class)
                ->searchable()
                ->sortable(),
        ];
    }

    public static function getForm()
    {
        return [
            TextInput::make('id_periode')
                ->label('ID')
                ->disabled()
                ->dehydrated(),
            Select::make('bulan')
                ->label('Bulan')
                ->options(BulanNomor::class)
                ->required()
                ->afterStateUpdated(fn(Get $get, Set $set) => $set('id_periode', $get('tahun').$get('bulan'))),
            TextInput::make('tahun')
                ->label('Tahun')
                ->required()
                ->numeric()
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                    return $rule
                        ->where('bulan', $get('bulan'))
                        ->where('tahun', $state);
                })
                ->validationMessages([
                    'unique' => 'Periode tersebut telah terdata.',
                ])
                ->afterStateUpdated(fn(Get $get, Set $set) => $set('id_periode', $get('tahun').$get('bulan'))),
            ToggleButtons::make('status')
                ->label('Status Periode')
                ->options(StatusPeriode::class)
        ];
    }
}
