<?php

namespace App\Models;

use App\Enums\BulanNomor;
use App\Enums\JenisKelamin;
use App\Enums\NilaiMateri;
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

class AkademikKediri extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'tes_akademik_kediri';

    protected $fillable = [
        'tes_santri_id',
        'guru_id',
        'nilai_makna',
        'nilai_keterangan',
        'nilai_penjelasan',
        'nilai_pemahaman',
        'catatan',
    ];

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Nilai Penyampaian '.$this->peserta->recordTitle,
        );
    }

    public function transform()
    {
        return [
            'id' => $this->id,
            'guru_id' => $this->guru_id,
            'guru_nama' => $this->guru->nama ?? null,
            'guru_foto' => $this->guru->getFilamentAvatarUrl(),
            'nilai_makna' => $this->nilai_makna,
            'nilai_keterangan' => $this->nilai_keterangan,
            'nilai_penjelasan' => $this->nilai_penjelasan,
            'nilai_pemahaman' => $this->nilai_pemahaman,
            'catatan' => $this->catatan,
            'created_at' => Carbon::parse($this->created_at)->translatedFormat('d F Y'),
        ];
    }

    public function peserta()
    {
        return $this->belongsTo(PesertaKediri::class, 'tes_santri_id');
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
                Tables\Columns\TextColumn::make('peserta.kelompok')
                    ->label('Klp')
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
                Tables\Columns\TextColumn::make('nilai_makna')
                    ->label('Makna')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilai_keterangan')
                    ->label('Keterangan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilai_penjelasan')
                    ->label('Penjelasan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilai_pemahaman')
                    ->label('Pemahaman')
                    ->sortable(),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50),
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
                        ->live()
                        ,
                ])
                ->visible(fn(string $operation) => $operation == 'create'),
            Forms\Components\Section::make('Penilaian')
                ->schema([
                    Forms\Components\Select::make('tes_santri_id')
                        ->label('Peserta')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                            return PesertaKediri::whereHas('siswa', function ($query) use ($get, $search) {
                                    $query->where('nama_lengkap', 'like', "%{$search}%")
                                        ->when($get('jenis_kelamin') != null, function ($q) use ($get) {
                                            return $q->where('jenis_kelamin', $get('jenis_kelamin')->value);
                                        });
                                })
                                ->when($get('bulan') != null, function ($q) use ($get) {
                                    return $q->where('periode_id', $get('tahun').$get('bulan'));
                                })
                                ->limit(10)
                                ->get()
                                ->mapWithKeys(fn ($peserta) => [$peserta->id => $peserta->siswa->nama_lengkap])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $peserta = PesertaKediri::with('siswa')->find($value);
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
                    Forms\Components\ToggleButtons::make('nilai_makna')
                        ->label('Nilai Makna')
                        ->options(NilaiMateri::class)
                        ->inline()
                        ->required(),
                    Forms\Components\ToggleButtons::make('nilai_keterangan')
                        ->label('Nilai Keterangan')
                        ->options(NilaiMateri::class)
                        ->inline()
                        ->required(),
                    Forms\Components\ToggleButtons::make('nilai_penjelasan')
                        ->label('Nilai Penjelasan')
                        ->options(NilaiMateri::class)
                        ->inline()
                        ->required(),
                    Forms\Components\ToggleButtons::make('nilai_pemahaman')
                        ->label('Nilai Pemahaman')
                        ->options(NilaiMateri::class)
                        ->inline()
                        ->required(),
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan')
                        ->nullable(),
                ])
                ->visible(fn(Get $get, string $operation) => ($get('bulan') && $get('tahun') && $get('jenis_kelamin')) || $operation != 'create'),
            ];
    }
}
