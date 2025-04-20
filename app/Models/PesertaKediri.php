<?php

namespace App\Models;

use App\Enums\HasilSistem;
use App\Enums\StatusKelanjutanKediri;
use App\Enums\StatusPeriode;
use App\Enums\StatusTesKediri;
use App\Enums\Tahap;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Unique;

class PesertaKediri extends Model
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
        //'kelompok' => KelompokKediri::class,
        'status_tes' => StatusTesKediri::class,
        'status_kelanjutan' => StatusKelanjutanKediri::class,
    ];

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->siswa->nama_lengkap.' ('.$this->id_periode.')',
        );
    }

    protected function riwayatTes(): Attribute
    {
        $riwayat_tes = PesertaKediri::where('nispn', $this->nispn)
            ->where('id_periode', '<', $this->id_periode)
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

    protected function asalDaerah(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->siswa->daerahSambung->n_daerah
        );
    }

    protected function asalPondokWithDaerah(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ponpes->namaWithDaerah
        );
    }

    public static function updateNomorCocardAndKelompok(): void
    {
        DB::transaction(function () {
            $kelompokList = range('A', 'T');
            $numKelompok = count($kelompokList); // Should be 20
            $periodeTesId = getPeriodeTes(); // Get the specific periode ID

            if (!$periodeTesId) {
                throw new \Exception("Periode Tes tidak ditemukan.");
            }

            // 1. Fetch participants for the specific periode, eager-loading required siswa data
            $pesertaData = PesertaKediri::where('id_periode', $periodeTesId)
                ->where('status_tes', StatusTesKediri::PRA_TES)
                ->with(['siswa' => function ($query) {
                    $query->select('nispn', 'jenis_kelamin', 'nama_lengkap');
                }])
                ->get();

            // 2. Group by gender
            $groupedByGender = $pesertaData->groupBy(function ($peserta) {
                return $peserta->siswa->jenis_kelamin ?? 'unknown';
            });

            $updates = []; // To store all update data

            // Process each gender group separately
            foreach ($groupedByGender as $gender => $pesertaGroup) {

                // 3. Sort participants within the gender group by name
                $sortedPeserta = $pesertaGroup->sortBy(function ($peserta) {
                    return strtolower(trim($peserta->siswa->nama_lengkap ?? ''));
                })->values(); // ->values() is important to ensure keys are 0, 1, 2...

                $totalPesertaInGroup = $sortedPeserta->count();
                if ($totalPesertaInGroup === 0) {
                    continue; // Skip empty groups
                }

                // 5. Calculate base size and remainder for distribution
                $baseSize = floor($totalPesertaInGroup / $numKelompok);
                $remainder = $totalPesertaInGroup % $numKelompok;

                $currentPesertaIndex = 0; // Pointer to the current participant in $sortedPeserta

                // 4, 6, 7, 8: Loop through each target Kelompok (A-T) and assign participants
                for ($groupIndex = 0; $groupIndex < $numKelompok; $groupIndex++) {
                    // Determine the target number of participants for *this* specific kelompok
                    $targetSizeForThisGroup = $baseSize + ($groupIndex < $remainder ? 1 : 0);

                    // Get the letter for the current kelompok
                    $assignedKelompok = $kelompokList[$groupIndex];

                    // Assign participants to this kelompok
                    for ($nomorInKelompok = 1; $nomorInKelompok <= $targetSizeForThisGroup; $nomorInKelompok++) {
                        // Safety check: ensure we don't try to access beyond the sorted list
                        if ($currentPesertaIndex >= $totalPesertaInGroup) {
                            // This should not happen if calculations are correct, but acts as a safeguard
                            Log::warning("Peserta index out of bounds during assignment.", [
                                'gender' => $gender,
                                'groupIndex' => $groupIndex,
                                'currentIndex' => $currentPesertaIndex,
                                'total' => $totalPesertaInGroup
                            ]);
                            break; // Exit inner loop for this group
                        }

                        // Get the participant to assign
                        $peserta = $sortedPeserta->get($currentPesertaIndex);

                        // Prepare the update data
                        if ($peserta) { // Ensure participant exists
                            $updates[$peserta->id_tes_santri] = [
                                'kelompok' => $assignedKelompok,
                                'nomor_cocard' => $nomorInKelompok,
                            ];
                        }

                        // Move to the next participant in the sorted list
                        $currentPesertaIndex++;
                    } // End loop for assigning participants within one kelompok

                    // Safety check: break outer loop if all participants assigned
                    if ($currentPesertaIndex >= $totalPesertaInGroup) {
                        break;
                    }

                } // End loop through kelompokList (A-T)

            } // End loop through gender groups

            // Perform the database updates
            if (!empty($updates)) {
                foreach ($updates as $id_tes_santri => $data) {
                    PesertaKediri::where('id_tes_santri', $id_tes_santri)
                        ->update([
                            'kelompok' => $data['kelompok'],
                            'nomor_cocard' => $data['nomor_cocard'],
                        ]);
                }
            }
        });
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
        return $this->hasMany(AkhlakKediri::class, 'tes_santri_id', 'id_tes_santri');
    }

    public function akademik()
    {
        return $this->hasMany(AkademikKediri::class, 'tes_santri_id', 'id_tes_santri');
    }

    public function scopeWithHasilSistem($query): void
    {
        $query->addSelect([
            // Kolom rata-rata individu tetap sama (jika masih dibutuhkan)
            'avg_nilai_makna' => AkademikKediri::selectRaw('AVG(nilai_makna)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_keterangan' => AkademikKediri::selectRaw('AVG(nilai_keterangan)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_penjelasan' => AkademikKediri::selectRaw('AVG(nilai_penjelasan)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            'avg_nilai_pemahaman' => AkademikKediri::selectRaw('AVG(nilai_pemahaman)')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            // Modifikasi avg_nilai untuk menggunakan bobot
            'avg_nilai' => AkademikKediri::selectRaw('
                (AVG(nilai_makna) * 0.20) +
                (AVG(nilai_keterangan) * 0.20) +
                (AVG(nilai_penjelasan) * 0.30) +
                (AVG(nilai_pemahaman) * 0.30)
            ')
                ->whereColumn('tes_santri_id', 'tb_tes_santri.id_tes_santri'),

            // Modifikasi hasil_sistem untuk menggunakan perhitungan bobot
            'hasil_sistem' => AkademikKediri::selectRaw("CASE
                WHEN (SELECT COUNT(*) FROM tes_akademik_kediri WHERE tes_akademik_kediri.tes_santri_id = tb_tes_santri.id_tes_santri) = 0
                    THEN '".HasilSistem::BELUM_PENGETESAN->getLabel()."'
                WHEN (
                    (AVG(nilai_makna) * 0.20) +
                    (AVG(nilai_keterangan) * 0.20) +
                    (AVG(nilai_penjelasan) * 0.30) +
                    (AVG(nilai_pemahaman) * 0.30)
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
            TextColumn::make('id_tes_santri')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('id_periode')
                ->label('Periode')
                ->sortable()
                ->searchable(),

            TextColumn::make('kelompok')
                ->label('Klp')
                ->badge()
                ->sortable(),

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

            SelectColumn::make('id_ponpes')
                ->label('Asal Pondok')
                ->options(
                    Ponpes::with('daerah')
                        ->join('tb_daerah', 'tb_ponpes.id_daerah', '=', 'tb_daerah.id_daerah')
                        ->orderByRaw('LOWER(tb_daerah.n_daerah), LOWER(tb_ponpes.n_ponpes)')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id_ponpes,
                                'name' => "{$item->n_ponpes} ({$item->daerah->n_daerah})"
                            ];
                        })
                        ->pluck('name', 'id')
                        ->toArray()
                )
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
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array =>
                        Siswa::where('nama_lengkap', 'like', "%{$search}%")
                            ->limit(50)
                            ->get() // Fetch data before mapping
                            ->map(function ($item) {
                                return [
                                    'id' => $item->nispn,
                                    'name' => "{$item->nama_lengkap} ({$item->nispn})"
                                ];
                            })
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->getOptionLabelUsing(function ($value) {
                            $siswa = Siswa::where('nispn', $value)->first();
                            return "{$siswa->nama_lengkap} ({$siswa->nispn})";
                        })
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
                    TextInput::make('kelompok')
                        ->label('Kelompok'),
                    TextInput::make('nomor_cocard')
                        ->label('Nomor Cocard')
                        ->numeric(),
                    Select::make('id_ponpes')
                        ->label('Asal Pondok')
                        ->options(Ponpes::with('daerah')->get()->map(function ($item) {
                            return [
                                'id' => $item->id_ponpes,
                                'name' => "{$item->n_ponpes} ({$item->daerah->n_daerah})"
                            ];
                        })->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                    Select::make('tahap')
                        ->label('Tahap Tes')
                        ->options(Tahap::class)
                        ->default(Tahap::KEDIRI->value)
                        ->disabled()
                        ->dehydrated()
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
        static::addGlobalScope('del_status', function ($builder) {
            $builder->where('tb_tes_santri.del_status', NULL);
        });
    }
}
