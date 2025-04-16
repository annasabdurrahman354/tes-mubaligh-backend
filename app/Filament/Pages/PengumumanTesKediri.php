<?php

namespace App\Filament\Pages;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Enums\StatusTesKediri;
use App\Models\Periode;
use App\Models\PesertaKediri;
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

class PengumumanTesKediri extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $slug = 'pengumuman-tes-kediri';
    protected static ?string $title = 'Pengumuman Tes Kediri';
    protected static ?string $navigationLabel = 'Pengumuman Tes';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static string $view = 'filament.pages.pengumuman-tes-kediri';
    public ?array $data = [];
    public $pengumuman = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->generatePengumuman();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pilih Kelompok Tes')
                    ->label('Filter Kelompok')
                    ->columns(2)
                    ->headerActions([
                        Action::make('generatePengumuman')
                            ->label('Generate Pengumuman')
                            ->action('generatePengumuman')
                            ->color('success'),
                    ])
                    ->schema([
                        SimpleAlert::make('alert')
                            ->title(function (){
                                $pesertaAktifCount = PesertaKediri::where('id_periode', $this->data['id_periode'])
                                    ->where('status_tes', StatusTesKediri::AKTIF->value)
                                    ->count();
                                return'Masih terdapat '. $pesertaAktifCount .' peserta yang belum ditentukan hasil tesnya!' ;
                            })
                            ->description('Pastikan semua peserta telah ditentukan hasil tesnya terlebih dahulu melalui halaman hasil tes.')
                            ->danger()
                            ->columnSpanFull()
                            ->border()
                            ->visible(fn() => PesertaKediri::where('id_periode', $this->data['id_periode'])
                                    ->where('status_tes', StatusTesKediri::AKTIF->value)
                                    ->count() != 0)
                            ->actions([
                                Action::make('redirectHasilTes')
                                    ->label('Rekap Hasil Tes')
                                    ->icon('heroicon-m-arrow-long-right')
                                    ->iconPosition(IconPosition::After)
                                    ->link()
                                    ->url(HasilTesKediri::getUrl())
                                    ->color('danger'),
                            ]),
                        Select::make('id_periode')
                            ->label('Periode Tes')
                            ->options(Periode::orderBy('id_periode', 'desc')->get()->pluck('id_periode', 'id_periode'))
                            ->searchable()
                            ->default(getPeriodeTes())
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                               $this->pengumuman = null;
                            }),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value)
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->pengumuman = null;
                            }),
                        Select::make('kelompok')
                            ->label('Kelompok')
                            ->options(KelompokKediri::class)
                            ->default(KelompokKediri::A->value)
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->pengumuman = null;
                            }),
                    ])
            ])
            ->statePath('data');
    }

    public function generatePengumuman()
    {
        $periode = getYearAndMonthName($this->data['id_periode']);

        $this->pengumuman = PesertaKediri::with(['siswa', 'ponpes', 'akhlak', 'akademik'])
            ->withHasilSistem()
            ->where('id_periode', $this->data['id_periode'])
            ->whereHas('siswa', function ($query) {
                $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
            })
            ->where('kelompok', $this->data['kelompok'])
            ->orderByRaw('CONVERT(nomor_cocard, SIGNED) ASC')
            ->get()
            ->map(function ($peserta) use ($periode) {
                return [
                    'nama_lengkap' => strtoupper(optional($peserta->siswa)->nama_lengkap),
                    'jenis_kelamin' => optional($peserta->siswa->jenis_kelamin)->value,
                    'kelompok' => $peserta->kelompok ?? null,
                    'nomor_cocard' => $peserta->nomor_cocard ?? null,
                    'daerah_sambung' => optional($peserta->siswa->daerahSambung)->n_daerah
                        ? strtoupper(optional($peserta->siswa->daerahSambung)->n_daerah)
                        : null,
                    'ponpes' => optional($peserta->ponpes)->n_ponpes
                        ? strtoupper(optional($peserta->ponpes)->n_ponpes)
                        : null,
                    'daerah_ponpes' => optional($peserta->ponpes->daerah)->n_daerah,
                    'poin_akhlak' => $peserta->totalPoinAkhlak,
                    'nilai_akademik' => $peserta->avg_nilai !== null ? number_format($peserta->avg_nilai, 1) : null,
                    'status' => optional($peserta->status_tes)->getLabel(),
                    'periode_bulan' => $periode['monthName'] ?? null,
                    'periode_tahun' => $periode['year'] ?? null,
                ];
            })
            ->toArray();
    }

    public function printPengumuman()
    {
        $this->js('document.getElementById("button-print").onclick = function() {
        document.body.innerHTML = document.getElementById("view-print").innerHTML;
        window.print();
        location.reload();
    };');
    }
}
