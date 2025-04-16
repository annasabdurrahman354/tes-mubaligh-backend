<?php

namespace App\Filament\Pages;

use App\Enums\BulanNomor;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PresensiGuruKediri extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $slug = 'presensi-guru-kediri';
    protected static ?string $title = 'Presensi Guru Kediri';
    protected static ?string $navigationLabel = 'Presensi Guru';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static string $view = 'filament.pages.presensi-guru-kediri';
    public ?array $data = [];

    public array $attendanceData = [];
    public array $distinctTanggalSesi = [];
    public string $bulan = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pilih Periode Tes')
                    ->label('Filter Bulan dan Tahun')
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
                            ->default(now()->format('m')),
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->default(now()->year),
                    ]),
            ])
            ->statePath('data');
    }

    public function generatePresensi()
    {
        $bulan = $this->data['bulan'] ?? now()->month;
        $tahun = $this->data['tahun'] ?? now()->year;

        $this->bulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->translatedFormat('F');

        $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $sessions = collect([
            ['label' => 'fajar', 'start' => '04:00:00', 'end' => '07:00:00'],
            ['label' => 'pagi', 'start' => '08:00:00', 'end' => '12:00:00'],
            ['label' => 'siang', 'start' => '13:00:00', 'end' => '15:30:00'],
            ['label' => 'malam', 'start' => '19:00:00', 'end' => '22:00:00'],
        ]);

        // Get all users with role "Guru Kediri"
        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn($query) => $query->where('name', 'Guru Kediri'))
            ->orderBy('username')
            ->get();

        // Get distinct dates from all AkademikKediri records within the selected month
        $distinctDates = DB::table('tes_akademik_kediri')
            ->selectRaw('DATE(created_at) as date')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->distinct()
            ->pluck('date')
            ->sort()
            ->values();

        // Create structure for table headers
        $this->distinctTanggalSesi = [];
        foreach ($distinctDates as $date) {
            $this->distinctTanggalSesi[$date] = $sessions;
        }

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

            $presensi = $user->akademikKediri()
                ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->get()
                ->groupBy(fn($item) => Carbon::parse($item->created_at)->toDateString());

            foreach ($this->distinctTanggalSesi as $date => $sessionList) {
                $attendance['tanggal'][$date]['sesi'] = [];

                foreach ($sessionList as $session) {
                    $count = 0;

                    if (isset($presensi[$date])) {
                        foreach ($presensi[$date] as $record) {
                            $createdAt = Carbon::parse($record->created_at);
                            $startSession = Carbon::parse($date . ' ' . $session['start']);
                            $endSession = Carbon::parse($date . ' ' . $session['end']);

                            if ($createdAt->between($startSession, $endSession)) {
                                $count++;
                            }
                        }
                    }

                    $attendance['tanggal'][$date]['sesi'][$session['label']] = $count;
                    $attendance['total_pertemuan']++;
                    $attendance['total_simak'] += $count; // Accumulate total_simak
                    if ($count > 0) {
                        $attendance['jumlah']['hadir']++;
                    } else {
                        $attendance['jumlah']['alpa']++;
                    }
                }
            }

            $total = $attendance['total_pertemuan'];
            $attendance['persen']['hadir'] = $total > 0 ? round($attendance['jumlah']['hadir'] / $total * 100, 1) . '%' : '0%';
            $attendance['persen']['alpa'] = $total > 0 ? round($attendance['jumlah']['alpa'] / $total * 100, 1) . '%' : '0%';

            $this->attendanceData[] = $attendance;
        }
    }

    public function printPresensi()
    {
        $this->js('document.getElementById("button-print").onclick = function() {
        document.body.innerHTML = document.getElementById("view-print").innerHTML;
        window.print();
        location.reload();
    };');
    }
}
