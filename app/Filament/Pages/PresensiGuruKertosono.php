<?php

namespace App\Filament\Pages;

use App\Enums\BulanNomor;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PresensiGuruKertosono extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $slug = 'presensi-guru-kertosono';
    protected static ?string $title = 'Presensi Guru Kertosono';
    protected static ?string $navigationLabel = 'Presensi Guru';
    protected static ?string $navigationGroup = 'Pengetesan Kertosono';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static string $view = 'filament.pages.presensi-guru-kertosono';
    public ?array $data = [];

    public array $attendanceData = [];
    public array $distinctTanggalSesi = [];
    public string $periodeLabel = '';

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
                        Action::make('generate')
                            ->label('Generate Presensi')
                            ->action('generatePresensi')
                            ->color('success'),
                    ])
                    ->schema([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(BulanNomor::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => $this->updateDatePickersBasedOnMonthYear($get, $set))
                            ->default(now()->format('m')),

                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(debounce: '500ms')
                            ->afterStateUpdated(fn (Get $get, Set $set) => $this->updateDatePickersBasedOnMonthYear($get, $set))
                            ->default(now()->year),

                        DatePicker::make('mulai_tanggal')
                            ->label('Mulai Tgl')
                            ->required()
                            ->native(false)
                            ->default(now()->startOfMonth()),

                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tgl')
                            ->required()
                            ->native(false)
                            ->default(now()->endOfMonth())
                    ]),
            ])
            ->statePath('data');
    }

    // Helper function signature updated for v3 Get/Set type hints
    private function updateDatePickersBasedOnMonthYear(Get $get, Set $set): void
    {
        $bulan = $get('bulan');
        $tahun = $get('tahun');

        if ($bulan && $tahun && is_numeric($tahun) && $tahun > 1) {
            try {
                $startOfMonth = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();

                // Use Set $set to update the other form fields
                $set('mulai_tanggal', $startOfMonth);
                $set('sampai_tanggal', $endOfMonth);
            } catch (InvalidFormatException $e) {
                Notification::make()
                    ->title('Tahun Tidak Valid')
                    ->warning()
                    ->body("Format tahun '$tahun' tidak valid.")
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Kesalahan Update Tanggal')
                    ->danger()
                    ->body("Tidak dapat mengupdate tanggal otomatis.")
                    ->send();
            }
        }
    }

    public function generatePresensi()
    {
        // --- Get filter values ---
        $bulanInput = $this->data['bulan'] ?? now()->month;
        $tahunInput = $this->data['tahun'] ?? now()->year;
        $mulaiTanggalInput = $this->data['mulai_tanggal'] ?? null;
        $sampaiTanggalInput = $this->data['sampai_tanggal'] ?? null;

        // --- Determine start and end dates ---
        if ($mulaiTanggalInput) {
            $start = Carbon::parse($mulaiTanggalInput)->startOfDay();
        } else {
            // Use selected month/year if start date is not provided
            $start = Carbon::createFromDate($tahunInput, $bulanInput, 1)->startOfMonth();
        }

        if ($sampaiTanggalInput) {
            $end = Carbon::parse($sampaiTanggalInput)->endOfDay();
        } else {
            // Use selected month/year if end date is not provided
            // Make sure end date respects the start date's month/year if start date IS provided
            $end = Carbon::createFromDate(
                $mulaiTanggalInput ? $start->year : $tahunInput,
                $mulaiTanggalInput ? $start->month : $bulanInput,
                1
            )->endOfMonth();

            // If an end date *wasn't* provided, but a start date *was*,
            // ensure the default end date isn't *before* the start date.
            // Use the end of the *start date's* month as the default end.
            if ($mulaiTanggalInput && $end->lt($start)) {
                $end = $start->copy()->endOfMonth();
            }
        }

        // --- Set labels for display ---
        // Set a more descriptive period label
        $this->periodeLabel = '' . $start->isoFormat('D MMM YYYY') . ' - ' . $end->isoFormat('D MMM YYYY');
        // Keep the 'bulan' property based on the initially selected month/year for potential consistency elsewhere if needed

        // --- Define Sessions ---
        $sessions = collect([
            ['label' => 'fajar', 'start' => '00:00:00', 'end' => '07:59:59'],
            ['label' => 'pagi', 'start' => '08:00:00', 'end' => '12:59:59'],
            ['label' => 'siang', 'start' => '13:00:00', 'end' => '18:59:59'],
            ['label' => 'malam', 'start' => '19:00:00', 'end' => '23:59:59'],
        ]);

        // --- Get Users ---
        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn($query) => $query->where('name', 'Guru Kertosono'))
            ->orderBy('username')
            ->get();

        // --- Get distinct dates based on the FINAL start and end dates ---
        $distinctDates = DB::table('tes_akademik_kertosono')
            ->selectRaw('DATE(created_at) as date')
            // Use the calculated $start and $end
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('date')
            ->sort()
            ->values();

        // --- Create structure for table headers ---
        $this->distinctTanggalSesi = [];
        foreach ($distinctDates as $date) {
            $this->distinctTanggalSesi[$date] = [];

            foreach ($sessions as $session) {
                $sessionStart = Carbon::parse("$date {$session['start']}");
                $sessionEnd = Carbon::parse("$date {$session['end']}");

                $exists = DB::table('tes_akademik_kertosono')
                    ->whereBetween('created_at', [$sessionStart, $sessionEnd])
                    ->exists();

                if ($exists) {
                    $this->distinctTanggalSesi[$date][] = $session;
                }
            }

            // Optional: remove the date if no session exists at all
            if (empty($this->distinctTanggalSesi[$date])) {
                unset($this->distinctTanggalSesi[$date]);
            }
        }

        // Re-key the array if dates were skipped (optional, depends on desired output)
        // $this->distinctTanggalSesi = array_values($this->distinctTanggalSesi);


        // --- Process Attendance Data ---
        $this->attendanceData = [];
        foreach ($users as $index => $user) {
            $attendance = [
                'no' => $index + 1,
                'nama' => $user->nama,
                'tanggal' => [],
                'jumlah' => ['hadir' => 0, 'alpa' => 0],
                'total_pertemuan' => 0,
                'total_simak' => 0, // Initialize total_simak
                'persen' => ['hadir' => '0%', 'alpa' => '0%'],
            ];

            // Fetch user's presensi within the FINAL start and end dates
            $presensi = $user->akademikKertosono()
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->groupBy(fn($item) => Carbon::parse($item->created_at)->toDateString());

            // Iterate through the dates *that actually have sessions* in the header
            foreach ($this->distinctTanggalSesi as $date => $sessionList) {
                $attendance['tanggal'][$date]['sesi'] = [];

                foreach ($sessionList as $session) {
                    $count = 0;

                    if (isset($presensi[$date])) {
                        foreach ($presensi[$date] as $record) {
                            $createdAt = Carbon::parse($record->created_at);
                            $startSession = Carbon::parse($date . ' ' . $session['start']);
                            $endSession = Carbon::parse($date . ' ' . $session['end']);

                            // Check if record creation time falls within the session time window
                            if ($createdAt->betweenIncluded($startSession, $endSession)) { // Use betweenIncluded for clarity
                                $count++;
                            }
                        }
                    }

                    $attendance['tanggal'][$date]['sesi'][$session['label']] = $count;
                    $attendance['total_pertemuan']++; // One potential meeting slot per session
                    $attendance['total_simak'] += $count; // Accumulate total simak count from records found

                    if ($count > 0) {
                        $attendance['jumlah']['hadir']++; // Mark session as attended if any record exists
                    } else {
                        $attendance['jumlah']['alpa']++; // Mark session as absent if no records found
                    }
                }
            }

            // Calculate percentages
            $total = $attendance['total_pertemuan'];
            $attendance['persen']['hadir'] = $total > 0 ? round($attendance['jumlah']['hadir'] / $total * 100, 1) . '%' : '0%';
            $attendance['persen']['alpa'] = $total > 0 ? round($attendance['jumlah']['alpa'] / $total * 100, 1) . '%' : '0%';

            $this->attendanceData[] = $attendance;
        }
        // dd($this->attendanceData, $this->distinctTanggalSesi, $start, $end); // For debugging
    }

    public function printPresensi()
    {
        $periodeLabel = $this->periodeLabel ?: now()->format('D MMM YYYY'); // fallback if empty
        $safeFilename = addslashes("Presensi Guru Tes - {$periodeLabel}");

        $this->js(<<<JS
        (function () {
            var uri = 'data:application/vnd.ms-excel;base64,';
            var template = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office"
                      xmlns:x="urn:schemas-microsoft-com:office:excel"
                      xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <!--[if gte mso 9]>
                        <meta charset="UTF-8">
                        <xml>
                            <x:ExcelWorkbook>
                                <x:ExcelWorksheets>
                                    <x:ExcelWorksheet>
                                        <x:Name>{worksheet}</x:Name>
                                        <x:WorksheetOptions>
                                            <x:DisplayGridlines/>
                                        </x:WorksheetOptions>
                                    </x:ExcelWorksheet>
                                </x:ExcelWorksheets>
                            </x:ExcelWorkbook>
                        </xml>
                        <![endif]-->
                    </head>
                    <body>
                        <table>{table}</table>
                    </body>
                </html>`;
            var base64 = function (s) {
                return window.btoa(unescape(encodeURIComponent(s)));
            };
            var format = function (s, c) {
                return s.replace(/{(\\w+)}/g, function (m, p) {
                    return c[p];
                });
            };
            var table = document.getElementById('table-print');
            if (!table) {
                alert('Table not found!');
                return;
            }
            var ctx = {
                worksheet: 'Presensi Guru Kertosono',
                table: table.innerHTML
            };
            var link = document.createElement('a');
            link.href = uri + base64(format(template, ctx));
            link.download = "{$safeFilename}.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        })();
    JS);
    }
}
