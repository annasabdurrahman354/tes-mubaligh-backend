<?php

namespace App\Models;

use App\Enums\HasilSistem;
use App\Enums\PenilaianKertosono;
use App\Enums\StatusKelanjutan;
use App\Enums\StatusKelanjutanKertosono;
use App\Enums\StatusPeriode;
use App\Enums\StatusTes;
use App\Enums\StatusTesKertosono;
use App\Enums\Tahap;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        'status_tes' => StatusTesKertosono::class,
        'status_kelanjutan' => StatusKelanjutanKertosono::class,
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

    /**
     * Updates the 'nomor_cocard' for participants in the 'PENGETESAN' period.
     * The numbering is based on sorting participants case-insensitively by:
     * 1. Gender (case-sensitive, assuming standard codes like 'L'/'P')
     * 2. Region name (case-insensitive)
     * 3. Ponpes name (case-insensitive)
     * 4. Full name (case-insensitive)
     *
     * @return void
     */
    public static function updateNomorCocard($periode): void
    {
        if (!Periode::where('id_periode', $periode)->first()) {
            throw new \Exception("Periode Tes tidak ditemukan.");
        }

        DB::transaction(function () use ($periode) {
            $sortedPesertaIds = PesertaKertosono::query()
                ->where('status_tes', StatusTesKertosono::AKTIF->value)
                ->where('id_periode', $periode)
                ->join('tb_personal_data', 'tb_tes_santri.nispn', '=', 'tb_personal_data.nispn')
                ->join('tb_ponpes', 'tb_tes_santri.id_ponpes', '=', 'tb_ponpes.id_ponpes')
                ->join('tb_daerah', 'tb_ponpes.id_daerah', '=', 'tb_daerah.id_daerah')
                ->orderBy('tb_personal_data.jenis_kelamin')
                // Use orderByRaw with LOWER() for case-insensitive sorting on text fields
                ->orderByRaw('LOWER(tb_daerah.n_daerah)')
                ->orderByRaw('LOWER(tb_ponpes.n_ponpes)')
                ->orderByRaw('LOWER(LTRIM(tb_personal_data.nama_lengkap))')
                ->select('tb_tes_santri.id_tes_santri')
                ->pluck('id_tes_santri'); // Pluck only the IDs after sorting

            $nomorCocardCounter = 1;
            // Loop through the correctly sorted IDs and update the nomor_cocard
            foreach ($sortedPesertaIds as $id) {
                PesertaKertosono::where('id_tes_santri', $id)
                    ->update(['nomor_cocard' => $nomorCocardCounter]);
                $nomorCocardCounter++;
            }
        });
    }

    /**
     * Creates PesertaKertosono records for the current testing period ('tes')
     * based on PesertaKediri from the previous month who passed ('lulus')
     * and were marked for continuation ('kertosono').
     *
     * @return int Number of participants successfully created. Returns -1 if current period not found.
     */
    public static function createKertosonoParticipantsFromPreviousMonth(): int
    {
        // 1. Find the current testing periode
        $currentPeriode = getPeriodeTes();

        if (!$currentPeriode) {
            Log::warning("createKertosonoParticipantsFromPreviousMonth: No active 'tes' periode found.");
            return -1; // Indicate error or specific status
        }

        // 2. Calculate the previous month's periode ID
        try {
            $previousMonthDate = Carbon::createFromFormat('Ym', $currentPeriode)
                ->startOfMonth() // Set to the first day for accurate month subtraction
                ->subMonth(); // Subtract one month
            $previousMonthPeriode = $previousMonthDate->format('Ym'); // e.g., "202412"
        } catch (\Exception $e) {
            Log::error("createKertosonoParticipantsFromPreviousMonth: Failed to parse current periode ID '{$currentPeriode}'. Error: " . $e->getMessage());
            return -1; // Indicate error
        }

        Log::info("createKertosonoParticipantsFromPreviousMonth: Current Periode ID: {$currentPeriode}, Previous Periode ID: {$previousMonthPeriode}");

        // 3. Find eligible PesertaKediri from the previous month
        // Ensure you have StatusTes::LULUS and StatusKelanjutan::KERTOSONO defined in your Enums
        // Or use the exact string values if not using Enums for these specific values
        $eligiblePesertaKediri = PesertaKediri::where('id_periode', $previousMonthPeriode)
            ->where('status_tes', StatusTes::LULUS->value) // Use Enum if available StatusTes::LULUS->value or 'lulus'
            ->where('status_kelanjutan', StatusKelanjutan::KERTOSONO->value) // Use Enum StatusKelanjutan::KERTOSONO->value or 'kertosono'
            ->get();

        if ($eligiblePesertaKediri->isEmpty()) {
            Log::info("createKertosonoParticipantsFromPreviousMonth: No eligible PesertaKediri found for periode {$previousMonthPeriode}.");
            return 0; // No participants to create
        }

        Log::info("createKertosonoParticipantsFromPreviousMonth: Found {$eligiblePesertaKediri->count()} eligible PesertaKediri.");

        $createdCount = 0;

        // 4. Create new PesertaKertosono records within a transaction
        DB::transaction(function () use ($eligiblePesertaKediri, $currentPeriode, &$createdCount) {
            foreach ($eligiblePesertaKediri as $pesertaKediri) {
                // 5. Check if a PesertaKertosono already exists for this nispn and current period
                $existing = PesertaKertosono::where('nispn', $pesertaKediri->nispn)
                    ->where('id_periode', $currentPeriode)
                    ->exists();

                if ($existing) {
                    Log::info("createKertosonoParticipantsFromPreviousMonth: Skipping NISPN {$pesertaKediri->nispn} - already exists in periode {$currentPeriode}.");
                    continue; // Skip if already exists
                }

                // 6. Prepare data and create the new PesertaKertosono record
                try {
                    PesertaKertosono::create([
                        'id_ponpes'         => $pesertaKediri->id_ponpes,
                        'id_periode'        => $currentPeriode, // Use the current period ID
                        'nispn'             => $pesertaKediri->nispn,
                        'tahap'             => Tahap::KERTOSONO->value, // Set Tahap Enum or 'kertosono'
                        'kelompok'          => null, // Set kelompok to null
                        'nomor_cocard'      => null, // Set nomor_cocard to null
                        'status_tes'        => StatusTesKertosono::PRA_TES->value, // Set StatusTes Enum or 'pra-tes'
                        'status_kelanjutan' => null, // Set status_kelanjutan to null
                    ]);
                    $createdCount++;
                    Log::info("createKertosonoParticipantsFromPreviousMonth: Created PesertaKertosono for NISPN {$pesertaKediri->nispn} in periode {$currentPeriode}.");
                } catch (\Exception $e) {
                    Log::error("createKertosonoParticipantsFromPreviousMonth: Failed to create PesertaKertosono for NISPN {$pesertaKediri->nispn}. Error: " . $e->getMessage());
                    // The transaction will automatically rollback on exception
                    throw $e; // Re-throw to trigger rollback
                }
            }
        }); // End transaction

        Log::info("createKertosonoParticipantsFromPreviousMonth: Successfully created {$createdCount} PesertaKertosono records for periode {$currentPeriode}.");

        return $createdCount; // Return the number of records created
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
        return $this->hasMany(AkhlakKertosono::class, 'tes_santri_id', 'id_tes_santri');
    }

    public function akademik()
    {
        return $this->hasMany(AkademikKertosono::class, 'tes_santri_id', 'id_tes_santri');
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
                        ->default(Tahap::KERTOSONO->value)
                        ->disabled()
                        ->dehydrated()
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
                            if ($get('status_tes') == StatusTesKertosono::LULUS->value) {
                                return [
                                    StatusKelanjutanKertosono::KEDIRI->value => StatusKelanjutanKertosono::KEDIRI->getLabel(),
                                ];
                            }
                            elseif (in_array($get('status_tes'), [StatusTesKertosono::TIDAK_LULUS_AKHLAK->value, StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value])) {
                                return [
                                    StatusKelanjutanKertosono::PONDOK_ASAL->value => StatusKelanjutanKertosono::PONDOK_ASAL->getLabel(),
                                    StatusKelanjutanKertosono::LENGKONG->value => StatusKelanjutanKertosono::LENGKONG->getLabel(),
                                    StatusKelanjutanKertosono::KERTOSONO->value => StatusKelanjutanKertosono::KERTOSONO->getLabel(),
                                ];
                            }
                        })
                        ->required(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKertosono::LULUS->value, StatusTesKertosono::TIDAK_LULUS_AKHLAK->value, StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value])
                        )
                        ->visible(
                            fn (Get $get) => in_array($get('status_tes'), [StatusTesKertosono::LULUS->value, StatusTesKertosono::TIDAK_LULUS_AKHLAK->value, StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value])
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
            $builder->where('tb_tes_santri.del_status', NULL);
        });
    }
}
