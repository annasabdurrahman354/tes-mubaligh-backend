<?php

namespace App\Filament\Pages;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Models\Periode;
use App\Models\PesertaKediri;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class RekapPenyimakanKediri extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $slug = 'rekap-penyimakan-kediri';
    protected static ?string $title = 'Rekap Penyimakan Kediri';
    protected static ?string $navigationLabel = 'Rekap Penyimakan';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.pages.rekap-penyimakan-kediri';
    public ?array $data = [];
    public $rekapanPerKelompok = null;
    public $guruKediri = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Data')
                    ->columns(2)
                    ->headerActions([
                        Action::make('generateRekapan')
                            ->label('Generate Per Kelompok')
                            ->action('generateRekapan')
                            ->color('success'),
                        Action::make('generateAllKelompok')
                            ->label(fn () => 'Generate Kelompok ' . $this->getJenisKelaminLabel())
                            ->action('generateAllKelompok')
                            ->color('info'), // Different color
                    ])
                    ->schema([
                        Select::make('id_periode')
                            ->label('Periode Tes')
                            ->options(Periode::orderBy('id_periode', 'desc')->get()->pluck('id_periode', 'id_periode'))
                            ->searchable()
                            ->default(getPeriodeTes())
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->rekapanPerKelompok = null;
                            }),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value)
                            ->required()
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->rekapanPerKelompok = null;
                            }),
                        Select::make('kelompok')
                            ->label('Kelompok')
                            ->options(KelompokKediri::class)
                            ->default(KelompokKediri::A->value)
                            ->required(fn (string $context) => $context === 'generateRekapan') // Required only for single generation
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old) {
                                $this->rekapanPerKelompok = null;
                            }),
                    ])
            ])
            ->statePath('data');
    }

    public function getJenisKelaminLabel(): string
    {
        if (empty($this->data['jenis_kelamin'])) {
            return 'Semua';
        }

        return JenisKelamin::from($this->data['jenis_kelamin'])->getLabel();
    }

    // Fetches Guru Kediri - reusable logic
    protected function fetchGuruKediri()
    {
        $this->guruKediri = User::with(['roles'])
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Guru Kediri');
            })
            ->orderBy('username')
            ->get();

        if ($this->guruKediri->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('Tidak Ditemukan Guru')
                ->body('Tidak ada pengguna dengan role Guru Kediri. Rekapan tidak dapat ditampilkan.')
                ->send();
            return false; // Indicate failure
        }
        return true; // Indicate success
    }

    // Generates rekapan for a SINGLE selected kelompok
    public function generateRekapan()
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

        $this->rekapanPerKelompok = []; // Reset before generating

        if (!$this->fetchGuruKediri()) {
            // Notification already sent in fetchGuruKediri
            return; // Stop if no guru found
        }

        $selectedKelompok = $this->data['kelompok'];

        $peserta = PesertaKediri::with([
                'siswa',
                'ponpes',
                'akademik' => function ($query) {
                    $query->select('id', 'tes_santri_id', 'guru_id');
                }
            ])
            ->where('id_periode', $this->data['id_periode'])
            ->whereHas('siswa', function ($query) {
                $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
            })
            ->where('kelompok', $selectedKelompok) // Use selected kelompok
            ->orderByRaw('CONVERT(nomor_cocard, SIGNED) ASC')
            ->get();

        if ($peserta->isNotEmpty()) {
            // Store under the selected kelompok key
            $this->rekapanPerKelompok[$selectedKelompok] = $peserta;
        } else {
            Notification::make()
                ->warning()
                ->title('Tidak Ditemukan Peserta')
                ->body("Tidak ada peserta yang cocok untuk Periode {$this->data['id_periode']}, Jenis Kelamin {$this->data['jenis_kelamin']}, dan Kelompok {$selectedKelompok}.")
                ->send();
        }

        // Check if, after trying, the overall result is empty
        if (empty($this->rekapanPerKelompok)) {
            // This case is mostly handled above, but good as a final check
            // Potentially add another notification if needed, though the specific one is better.
        }
    }

    // Generates rekapan for ALL kelompok for the selected periode and jenis kelamin
    public function generateAllKelompok()
    {
        $this->rekapanPerKelompok = []; // Reset before generating

        if (!$this->fetchGuruKediri()) {
            // Notification already sent in fetchGuruKediri
            return; // Stop if no guru found
        }

        $foundAnyPeserta = false;
        // Loop through all possible Kelompok values
        foreach (KelompokKediri::cases() as $kelompokEnum) {
            $kelompokValue = $kelompokEnum->value;

            $peserta = PesertaKediri::with([
                'siswa',
                'ponpes',
                'akademik' => function ($query) {
                    $query->select('id', 'tes_santri_id', 'guru_id');
                }
            ])
                ->where('id_periode', $this->data['id_periode'])
                ->whereHas('siswa', function ($query) {
                    $query->where('jenis_kelamin', $this->data['jenis_kelamin']);
                })
                ->where('kelompok', $kelompokValue) // Use current kelompok in loop
                ->orderByRaw('CONVERT(nomor_cocard, SIGNED) ASC')
                ->get();

            if ($peserta->isNotEmpty()) {
                $this->rekapanPerKelompok[$kelompokValue] = $peserta;
                $foundAnyPeserta = true;
            }
        }

        // Sort the results by kelompok key (A, B, C...)
        ksort($this->rekapanPerKelompok);

        if (!$foundAnyPeserta) {
            Notification::make()
                ->warning()
                ->title('Tidak Ditemukan Peserta')
                ->body("Tidak ada peserta yang cocok untuk Periode {$this->data['id_periode']} dan Jenis Kelamin {$this->data['jenis_kelamin']} di semua kelompok.")
                ->send();
        }
    }

    public function printRekapan()
    {
        // This JS targets the #view-print div, which will now contain multiple tables.
        // The browser's print functionality combined with CSS page breaks will handle separation.
        $this->js('
            const printContent = document.getElementById("view-print");
            if (printContent) {
                const originalBody = document.body.innerHTML;
                document.body.innerHTML = printContent.innerHTML;
                window.print();
                document.body.innerHTML = originalBody; // Restore original content
                location.reload(); // Reload to restore Livewire state if needed
            } else {
                console.error("Element with ID view-print not found.");
                alert("Error: Could not find content to print.");
            }
        ');
        // Added error handling and better restore logic to the JS
    }
}
