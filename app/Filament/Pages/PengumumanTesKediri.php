<?php

namespace App\Filament\Pages;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Enums\StatusTesKediri;
use App\Models\PesertaKediri;
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
        $periode_pengetesan_id = getPeriodeTes();
        $pesertaAktifCount = PesertaKediri::where('periode_id', $periode_pengetesan_id)
            ->where('status_tes', StatusTesKediri::AKTIF->value)
            ->count();

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
                            ->title('Masih terdapat '. $pesertaAktifCount .' peserta yang belum ditentukan hasil tesnya!')
                            ->description('Pastikan semua peserta telah ditentukan hasil tesnya terlebih dahulu melalui halaman hasil tes.')
                            ->danger()
                            ->columnSpanFull()
                            ->border()
                            ->visible($pesertaAktifCount != 0)
                            ->actions([
                                Action::make('redirectHasilTes')
                                    ->label('Rekap Hasil Tes')
                                    ->icon('heroicon-m-arrow-long-right')
                                    ->iconPosition(IconPosition::After)
                                    ->link()
                                    ->url(HasilTesKediri::getUrl())
                                    ->color('danger'),
                            ]),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value)
                            ->columnSpan(1),
                        Select::make('kelompok')
                            ->label('Kelompok')
                            ->options(KelompokKediri::class)
                            ->default(KelompokKediri::A->value)
                            ->columnSpan(1),
                    ])
            ])
            ->statePath('data');
    }

    public function generatePengumuman()
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);

        $this->pengumuman = PesertaKediri::with(['siswa', 'ponpes', 'akhlak', 'akademik'])
            ->withHasilSistem()
            ->where('periode_id', $periode_pengetesan_id)
            ->whereHas('siswa', function ($query) {
                $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
            })
            ->where('kelompok', $this->data['kelompok'])
            ->orderBy('nomor_cocard')
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
}
