<?php

namespace App\Models;

use App\Enums\BulanNomor;
use App\Enums\JenisKelamin;
use App\Enums\KekuranganKelancaran;
use App\Enums\KekuranganKeserasian;
use App\Enums\KekuranganKhusus;
use App\Enums\KekuranganTajwid;
use App\Enums\PenilaianKertosono;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class AkademikKertosono extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'tes_akademik_kertosono';

    protected $fillable = [
        'tes_santri_id',
        'guru_id',
        'penilaian',
        'kekurangan_tajwid',
        'kekurangan_khusus',
        'kekurangan_keserasian',
        'kekurangan_kelancaran',
        'catatan',
        'rekomendasi_penarikan',
        'durasi_penilaian',
    ];

    protected $casts = [
        'kekurangan_tajwid' => 'array',
        'kekurangan_khusus' => 'array',
        'kekurangan_keserasian' => 'array',
        'kekurangan_kelancaran' => 'array',
        'penilaian' => PenilaianKertosono::class
    ];

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Nilai Bacaan '.$this->peserta->recordTitle,
        );
    }

    protected function allKekurangan(): Attribute
    {
        return Attribute::make(
            get: fn () => array_merge(
                $this->kekurangan_tajwid ?? [],
                $this->kekurangan_khusus ?? [],
                $this->kekurangan_keserasian ?? [],
                $this->kekurangan_kelancaran ?? [],
            )
        );
    }

    public function transform()
    {
        return [
            'id' => $this->id,
            'guru_id' => $this->guru_id,
            'guru_nama' => $this->guru->nama ?? null,
            'guru_foto' => $this->guru->getFilamentAvatarUrl(),
            'penilaian' => $this->penilaian,
            'kekurangan_kelancaran' => $this->kekurangan_kelancaran,
            'kekurangan_keserasian' => $this->kekurangan_keserasian,
            'kekurangan_khusus' => $this->kekurangan_khusus,
            'kekurangan_tajwid' => $this->kekurangan_tajwid,
            'catatan' => $this->catatan,
            'rekomendasi_penarikan' => $this->rekomendasi_penarikan,
            'durasi_penilaian' => $this->durasi_penilaian,
            'created_at' => Carbon::parse($this->created_at)->translatedFormat('d F Y'),
        ];
    }

    public function peserta()
    {
        return $this->belongsTo(PesertaKertosono::class, 'tes_santri_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public static function getColumns()
    {
        return [
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('peserta.periode.id_periode')
                    ->label('Periode')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('peserta.nomor_cocard')
                    ->label('No')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('peserta.siswa.nama_lengkap')
                    ->label('Peserta')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('guru.nama')
                    ->label('Guru')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('penilaian')
                    ->label('Penilaian')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kekurangan_tajwid')
                    ->label('Tajwid')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kekurangan_khusus')
                    ->label('Khusus')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kekurangan_keserasian')
                    ->label('Keserasian')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kekurangan_kelancaran')
                    ->label('Kelancaran')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50),
                Tables\Columns\IconColumn::make('rekomendasi_penarikan')
                    ->label('Rekomendasi Penarikan')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_penilaian')
                    ->label('Lama Penilaian'),
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
            Forms\Components\Section::make('Periode Pengetesan')
                ->schema([
                    Select::make('bulan')
                        ->label('Bulan')
                        ->options(BulanNomor::class)
                        ->required()
                        ->default(now()->format('m'))
                        ->live(),
                    TextInput::make('tahun')
                        ->label('Tahun')
                        ->default(now()->year)
                        ->numeric()
                        ->required()
                        ->live(),
                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options(JenisKelamin::class)
                        ->default(JenisKelamin::LAKI_LAKI)
                        ->required()
                        ->live(),
                ])
                ->visible(fn(string $operation) => $operation == 'create'),
            Forms\Components\Section::make('Penilaian')
                ->schema([
                    Forms\Components\Select::make('tes_santri_id')
                        ->label('Peserta')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                            return PesertaKertosono::whereHas('siswa', function ($query) use ($get, $search) {
                                    $query->where('nama_lengkap', 'like', "%{$search}%")
                                        ->when($get('jenis_kelamin') != null, function ($q) use ($get) {
                                            return $q->where('jenis_kelamin', $get('jenis_kelamin')->value);
                                        });
                                })
                                ->when($get('bulan') != null, function ($q) use ($get) {
                                    return $q->where('id_periode', $get('tahun').$get('bulan'));
                                })
                                ->limit(10)
                                ->get()
                                ->mapWithKeys(fn ($peserta) => [$peserta->id => $peserta->siswa->nama_lengkap])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $peserta = PesertaKertosono::with('siswa')->find($value);
                            return $peserta?->siswa?->nama_lengkap;
                        })
                        ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                            return $rule
                                ->where('guru_id', $get('guru_id'))
                                ->where('tes_santri_id', $state);
                        })
                        ->validationMessages([
                            'unique' => 'Santri sudah dinilai oleh guru tersebut.',
                        ])
                        ->required()
                        ->disabledOn('edit'),
                    Forms\Components\Select::make('guru_id')
                        ->label('Guru')
                        ->relationship(
                            name:'guru',
                            titleAttribute: 'nama',
                        )
                        ->searchable()
                        ->default(Auth::user()->id)
                        ->required()
                        ->disabledOn('edit'),
                    Forms\Components\ToggleButtons::make('penilaian')
                        ->label('Penilaian')
                        ->options(PenilaianKertosono::class)
                        ->inline()
                        ->required()
                        ->live(),
                    Forms\Components\CheckboxList::make('kekurangan_tajwid')
                        ->label('Kekurangan')
                        ->options(KekuranganTajwid::class)
                        ->columns(2)
                        ->gridDirection('row')
                        ->visible(fn(Forms\Get $get) => $get('penilaian') == PenilaianKertosono::TIDAK_LULUS->value),
                    Forms\Components\CheckboxList::make('kekurangan_khusus')
                        ->label('Kekurangan')
                        ->options(KekuranganKhusus::class)
                        ->columns(2)
                        ->gridDirection('row')
                        ->visible(fn(Forms\Get $get) => $get('penilaian') == PenilaianKertosono::TIDAK_LULUS->value),
                    Forms\Components\CheckboxList::make('kekurangan_keserasian')
                        ->label('Kekurangan')
                        ->options(KekuranganKeserasian::class)
                        ->columns(2)
                        ->gridDirection('row')
                        ->visible(fn(Forms\Get $get) => $get('penilaian') == PenilaianKertosono::TIDAK_LULUS->value),
                    Forms\Components\CheckboxList::make('kekurangan_kelancaran')
                        ->label('Kekurangan')
                        ->options(KekuranganKelancaran::class)
                        ->columns(2)
                        ->gridDirection('row')
                        ->visible(fn(Forms\Get $get) => $get('penilaian') == PenilaianKertosono::TIDAK_LULUS->value),
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan')
                        ->nullable(),
                    Forms\Components\Checkbox::make('rekomendasi_penarikan')
                        ->label('Rekomendasi Penarikan'),
                    Forms\Components\TextInput::make('durasi_penilaian')
                        ->label('Lama Penilaian')
                        ->numeric()
                        ->integer(),
                ])
                ->visible(fn(Get $get, string $operation) => ($get('bulan') && $get('tahun') && $get('jenis_kelamin')) || $operation != 'create'),
        ];
    }
}
