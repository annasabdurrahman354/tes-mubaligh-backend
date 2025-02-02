<?php

namespace Database\Seeders;

use App\Enums\StatusTesKediri;
use App\Enums\StatusTesKertosono;
use App\Models\PesertaKediri;
use App\Models\PesertaKertosono;
use App\Models\Ponpes;
use App\Models\Siswa;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InitialSeeder::class,
        ]);

        $currentDate = Carbon::now();
        $thisMonth = $currentDate->format('Ym');
        $currentDate = Carbon::now()->subMonth();
        $thisMonthData = [
            'id_periode' => $thisMonth,
            'bulan' => $currentDate->copy()->addMonth()->format('m'),
            'tahun' => $currentDate->copy()->addMonth()->year,
            'status' => 'tes'
        ];

        $nextMonth = Carbon::now()->addMonth();
        $nextMonthId = $nextMonth->format('Ym');
        $nextMonth = Carbon::now();
        $nextMonthData = [
            'id_periode' => $nextMonthId,
            'bulan' => $nextMonth->copy()->addMonth()->format('m'),
            'tahun' => $nextMonth->copy()->addMonth()->year,
            'status' => 'pendaftaran'
        ];

        // Insert the data into the database
        DB::table('tb_periode')->insert([
            $thisMonthData,
            $nextMonthData,
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersTableSeeder::class,
            //SiswaTableSeeder::class
        ]);

        $panels = Filament::getPanels();
        foreach ($panels as $panelId => $panel) {
            Filament::setCurrentPanel($panel);
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => $panelId,
            ]);
        }

        //$this->assignSiswaToPesertaKediri($thisMonthData['id_periode']);
        //$this->assignSiswaToPesertaKertosono($thisMonthData['id_periode']);
    }

    private function assignSiswaToPesertaKediri(string $periodeId): void
    {
        // Get students grouped by gender, sorted by name within each gender
        $siswaByGender = Siswa::orderBy('nama_lengkap')
            ->get()
            ->groupBy('jenis_kelamin');

        $groups = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];

        // Process each gender separately
        foreach ($siswaByGender as $gender => $siswaList) {
            $totalStudents = $siswaList->count();

            // Use all groups (A to T) for each gender
            $numberOfGroups = count($groups);

            // Calculate base size and remainder for even distribution
            $baseGroupSize = (int)floor($totalStudents / $numberOfGroups);
            $remainingStudents = $totalStudents % $numberOfGroups;

            $currentStudent = 0;

            // Distribute students across all groups
            for ($groupIndex = 0; $groupIndex < $numberOfGroups; $groupIndex++) {
                $kelompok = $groups[$groupIndex];

                // Calculate size for this group (add one extra if there are remaining students)
                $studentsInThisGroup = $baseGroupSize + ($groupIndex < $remainingStudents ? 1 : 0);

                // Skip empty groups
                if ($studentsInThisGroup === 0) {
                    continue;
                }

                // Get students for current group
                $currentGroup = $siswaList->slice($currentStudent, $studentsInThisGroup);
                $currentStudent += $studentsInThisGroup;

                // Assign students to this group with cocard numbers starting from 1
                $cocardNumber = 1;
                foreach ($currentGroup as $siswa) {
                    PesertaKediri::create([
                        'ponpes_id' => Ponpes::inRandomOrder()->first()?->id_ponpes,
                        'periode_id' => $periodeId,
                        'nispn' => $siswa->nispn,
                        'tahap' => 'kediri',
                        'kelompok' => $kelompok,
                        'nomor_cocard' => $cocardNumber,
                        'status_tes' => StatusTesKediri::AKTIF->value,
                    ]);

                    $cocardNumber++;
                }
            }
        }
    }

    private function assignSiswaToPesertaKertosono(string $periodeId): void
    {
        $siswaList = Siswa::orderBy('id_daerah_sambung')->orderBy('nama_lengkap')
            ->get();

        $cocardNumber = 1;

        foreach ($siswaList as $siswa) {
            PesertaKertosono::create([
                'ponpes_id' => Ponpes::inRandomOrder()->first()?->id_ponpes,
                'periode_id' => $periodeId,
                'nispn' => $siswa->nispn,
                'tahap' => 'kertosono',
                'nomor_cocard' => $cocardNumber,
                'status_tes' => StatusTesKertosono::AKTIF->value,
            ]);
            $cocardNumber++;
        }
    }
}
