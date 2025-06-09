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
use Filament\Notifications\Notification; // Added for notifications
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
    // Changed state variable to hold data per group
    public $pengumumanPerKelompok = null;

    public function mount(): void
    {
        // Fill form, but don't generate automatically on mount
        $this->form->fill();
        // $this->generatePengumuman(); // Removed auto-generation
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Data')
                    ->columns(2)
                    ->headerActions([
                        // Action for generating single kelompok
                        Action::make('generatePengumumanKelompok')
                            ->label('Generate Per Kelompok')
                            ->action('generatePengumumanKelompok')
                            ->color('success'),
                        // Action for generating all kelompok for selected gender
                        Action::make('generatePengumumanAllKelompok')
                            ->label(fn () => 'Generate Kelompok ' . $this->getJenisKelaminLabel())
                            ->action('generatePengumumanAllKelompok')
                            ->color('info'),
                    ])
                    ->schema([
                        SimpleAlert::make('alert')
                            ->title(function (){
                                $pesertaAktifCount = PesertaKediri::where('id_periode', $this->data['id_periode'])
                                    ->where('status_tes', StatusTesKediri::AKTIF->value)
                                    ->count();
                                return 'Masih terdapat '. $pesertaAktifCount .' peserta yang belum ditentukan hasil tesnya!' ;
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
                                    ->url(HasilTesKediri::getUrl()) // Assuming HasilTesKediri has getUrl()
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
                                // Reset data when filter changes
                                $this->pengumumanPerKelompok = null;
                            }),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value)
                            ->required() // Keep required as it's needed for both actions
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->pengumumanPerKelompok = null;
                            }),
                        Select::make('kelompok')
                            ->label('Kelompok')
                            ->options(KelompokKediri::class)
                            ->default(KelompokKediri::A->value)
                            // Not strictly required overall, but needed for 'generatePengumumanKelompok'
                            // Validation happens inside the action method
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->pengumumanPerKelompok = null;
                            }),
                    ])
            ])
            ->statePath('data');
    }

    // Helper function to get gender label (copied from RekapPenyimakanKediri)
    public function getJenisKelaminLabel(): string
    {
        if (empty($this->data['jenis_kelamin'])) {
            // Should not happen if required() is set, but good fallback
            return 'Semua';
        }
        // Ensure the value exists in the Enum before trying to use it
        $enumValue = JenisKelamin::tryFrom($this->data['jenis_kelamin']);
        return $enumValue ? $enumValue->getLabel() : 'Tidak Valid';
    }

    // Helper function to fetch and format announcement data for a given kelompok
    private function fetchPengumumanData(string $kelompokValue): array
    {
        $periode = getYearAndMonthName($this->data['id_periode']);

        return PesertaKediri::with(['siswa', 'ponpes', 'akhlak', 'akademik'])
            ->withHasilSistem()
            ->where('id_periode', $this->data['id_periode'])
            ->whereIn('status_tes', [StatusTesKediri::AKTIF, StatusTesKediri::LULUS, StatusTesKediri::TIDAK_LULUS_AKADEMIK, StatusTesKediri::TIDAK_LULUS_AKHLAK])
            ->whereHas('siswa', function ($query) {
                $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
            })
            ->where('kelompok', $kelompokValue) // Filter by the specific kelompok
            ->orderByRaw('CONVERT(nomor_cocard, SIGNED) ASC')
            ->get()
            ->map(function ($peserta) use ($periode) {
                return [
                    'nama_lengkap' => strtoupper($peserta->siswa?->nama_lengkap ?? ''),
                    'jenis_kelamin' => $peserta->siswa?->jenis_kelamin?->value ?? null,
                    'kelompok' => $peserta->kelompok ?? null,
                    'nomor_cocard' => $peserta->nomor_cocard ?? null,
                    'daerah_sambung' => $peserta->siswa?->daerahSambung?->n_daerah
                        ? strtoupper($peserta->siswa->daerahSambung->n_daerah)
                        : null,
                    'ponpes' => $peserta->ponpes?->n_ponpes
                        ? strtoupper(trim(str_replace($peserta->ponpes?->daerah?->n_daerah ?? "", "", $peserta->ponpes->n_ponpes)))
                        : null,
                    'daerah_ponpes' => $peserta->ponpes?->daerah?->n_daerah ?? null,
                    'poin_akhlak' => $peserta->totalPoinAkhlak ?? null,
                    'nilai_akademik' => $peserta->avg_nilai !== null ? number_format($peserta->avg_nilai, 1) : null,
                    'status' => $peserta->status_tes?->getLabel() ?? null,
                    'periode_bulan' => $periode['monthName'] ?? null,
                    'periode_tahun' => $periode['year'] ?? null,
                ];
            })
            ->toArray();
    }

    // Action to generate announcement for a SINGLE selected kelompok
    public function generatePengumumanKelompok()
    {
        // Validate that kelompok is selected for this specific action
        if (empty($this->data['kelompok'])) {
            Notification::make()
                ->danger()
                ->title('Kelompok Belum Dipilih')
                ->body('Silakan pilih Kelompok terlebih dahulu untuk generate per kelompok.')
                ->send();
            return;
        }

        $this->pengumumanPerKelompok = []; // Reset before generating

        $selectedKelompok = $this->data['kelompok'];
        $pengumumanData = $this->fetchPengumumanData($selectedKelompok);

        if (!empty($pengumumanData)) {
            $this->pengumumanPerKelompok[$selectedKelompok] = $pengumumanData;
        } else {
            Notification::make()
                ->warning()
                ->title('Tidak Ditemukan Peserta')
                ->body("Tidak ada peserta yang cocok untuk Periode {$this->data['id_periode']}, Jenis Kelamin {$this->getJenisKelaminLabel()}, dan Kelompok {$selectedKelompok}.")
                ->send();
        }
    }

    // Action to generate announcement for ALL kelompok for the selected gender
    public function generatePengumumanAllKelompok()
    {
        $this->pengumumanPerKelompok = []; // Reset before generating
        $foundAnyPeserta = false;

        foreach (KelompokKediri::cases() as $kelompokEnum) {
            $kelompokValue = $kelompokEnum->value;
            $pengumumanData = $this->fetchPengumumanData($kelompokValue);

            if (!empty($pengumumanData)) {
                $this->pengumumanPerKelompok[$kelompokValue] = $pengumumanData;
                $foundAnyPeserta = true;
            }
        }

        // Sort the results by kelompok key (A, B, C...)
        ksort($this->pengumumanPerKelompok);

        if (!$foundAnyPeserta) {
            Notification::make()
                ->warning()
                ->title('Tidak Ditemukan Peserta')
                ->body("Tidak ada peserta yang cocok untuk Periode {$this->data['id_periode']} dan Jenis Kelamin {$this->getJenisKelaminLabel()} di semua kelompok.")
                ->send();
        }
    }


    // Keep the original print function - it targets #view-print which will now contain all groups
    public function printPengumuman()
    {
        // Slightly more robust JS
        $this->js('
            const printContent = document.getElementById("view-print");
            const printButton = document.getElementById("button-print");
            if (printButton) {
                printButton.style.display = "none"; // Hide button itself for print
            }
            if (printContent) {
                const originalBody = document.body.innerHTML;
                const originalTitle = document.title;

                // Try to find a relevant title
                const periode = "' . ($this->data['id_periode'] ?? 'N/A') .'";
                const gender = "' . ($this->getJenisKelaminLabel() ?? 'N/A') .'";
                document.title = `Pengumuman Tes Kediri - ${periode} - ${gender}`;

                document.body.innerHTML = printContent.innerHTML;
                window.print();
                document.body.innerHTML = originalBody; // Restore original content
                document.title = originalTitle; // Restore title
                location.reload(); // Reload to restore Livewire/Filament state
            } else {
                console.error("Element with ID view-print not found.");
                alert("Error: Could not find content to print.");
                if (printButton) {
                    printButton.style.display = ""; // Show button again if print failed
                }
            }
        ');
    }
}
