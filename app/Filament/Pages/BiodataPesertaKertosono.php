<?php

namespace App\Filament\Pages;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Enums\StatusPeriode;
use App\Enums\StatusTes;
use App\Enums\StatusTesKediri;
use App\Models\Periode;
use App\Models\PesertaKediri;
use App\Models\PesertaKertosono;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use CodeWithDennis\SimpleAlert\Components\Forms\SimpleAlert;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;

class BiodataPesertaKertosono extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $slug = 'biodata-peserta-kertosono';
    protected static ?string $title = 'Biodata Peserta Kertosono';
    protected static ?string $navigationLabel = 'Biodata Peserta';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.biodata-peserta-kertosono';
    public ?array $data = [];
    public $biodata = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Peserta')
                    ->label('Filter Peserta')
                    ->columns(2)
                    ->headerActions([
                        Action::make('generateBiodata')
                            ->label('Generate Biodata')
                            ->action('generateBiodata')
                            ->color('success'),
                    ])
                    ->schema([
                        Select::make('id_periode')
                            ->label('Periode Tes')
                            ->options(Periode::orderBy('id_periode', 'desc')->get()->pluck('id_periode', 'id_periode'))
                            ->searchable()
                            ->default(getPeriodeTes())
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                               $this->biodata = null;
                            }),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value)
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->biodata = null;
                            }),
                    ])
            ])
            ->statePath('data');
    }

    public function generateBiodata()
    {
        $this->biodata = PesertaKertosono::whereHas('siswa', function ($query) {
                $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
            })
            ->with('siswa')
            ->where('id_periode', $this->data['id_periode'])
            ->join('tb_personal_data', 'tb_tes_santri.nispn', '=', 'tb_personal_data.nispn')
            ->join('tb_ponpes', 'tb_tes_santri.id_ponpes', '=', 'tb_ponpes.id_ponpes')
            ->join('tb_daerah', 'tb_ponpes.id_daerah', '=', 'tb_daerah.id_daerah')
            ->orderBy('tb_personal_data.jenis_kelamin')
            // Use orderByRaw with LOWER() for case-insensitive sorting on text fields
            ->orderByRaw('LOWER(tb_daerah.n_daerah)')
            ->orderByRaw('LOWER(tb_ponpes.n_ponpes)')
            ->orderByRaw('LOWER(LTRIM(tb_personal_data.nama_lengkap))')
            ->get()
            ->map(function ($peserta) {
                return [
                    'ponpes' => $peserta->ponpes->namaWithDaerah,
                    'jenis_kelamin' => $peserta->siswa->jenis_kelamin,
                    'nama_lengkap' => $peserta->siswa->nama_lengkap,
                    'nama_ayah' => $peserta->siswa->nama_ayah,
                    'tempat_lahir' => $peserta->siswa->tempat_lahir,
                    'tanggal_lahir' => $peserta->siswa->tanggal_lahir ? $peserta->siswa->tanggal_lahir->format('Y-m-d') : null,
                    'alamat' => "{$peserta->siswa->alamat}, RT{$peserta->siswa->rt}/RW{$peserta->siswa->rw}",
                    'desa_kel' => $peserta->siswa->desa_kel,
                    'kecamatan' => $peserta->siswa->kecamatan,
                    'kota_kab' => $peserta->siswa->kota_kab,
                    'hp' => $peserta->siswa->hp,
                    'daerah_sambung' => $peserta->siswa->daerahSambung->n_daerah,
                    'kelompok_sambung' => $peserta->siswa->kelompok_sambung,
                    'pendidikan' => $peserta->siswa->pendidikan,
                    'status_mondok' => $peserta->siswa->status_mondok, // Get the enum value
                ];
            })
            ->toArray();
    }

    public function printBiodata()
    {
        $this->js('document.getElementById("button-print").onclick = function() {
        document.body.innerHTML = document.getElementById("view-print").innerHTML;
        window.print();
        location.reload();
    };');
    }
}
