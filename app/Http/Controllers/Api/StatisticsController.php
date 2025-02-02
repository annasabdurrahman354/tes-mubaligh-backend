<?php

namespace App\Http\Controllers\Api;

use App\Enums\HasilSistem;
use App\Enums\StatusTes;
use App\Enums\StatusTesKediri;
use App\Enums\StatusTesKertosono;
use App\Models\PesertaKediri;
use App\Models\AkademikKediri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\JenisKelamin;
use App\Models\PesertaKertosono;
use App\Models\AkademikKertosono;

class StatisticsController extends Controller
{
    public function getStatistikKediri(Request $request)
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);
        $periode_tes = $periode['monthName'] . ' ' . $periode['year'];

        // Base query to avoid duplication
        $baseQuery = PesertaKediri::where('periode_id', $periode_pengetesan_id)
            ->whereNotIn('status_tes', [
                StatusTesKediri::PRA_TES->value,
                StatusTesKediri::TUNDA->value
            ])
            ->withHasilSistem();

        // Fetch data in one query to avoid multiple DB hits
        $pesertaData = $baseQuery->with(['siswa', 'akademik'])->get();

        // Compute overall stats
        $overallStats = $this->calculateStats($pesertaData);

        // Compute stats by gender
        $statsByGender = collect(JenisKelamin::cases())->mapWithKeys(function ($gender) use ($pesertaData) {
            return [$gender->value => $this->calculateStats(
                $pesertaData->where('siswa.jenis_kelamin', $gender)
            )];
        });

        return response()->json([
            'periode_tes' => $periode_tes,
            'overall' => $overallStats,
            'by_gender' => $statsByGender
        ]);
    }


    public function getStatistikKertosono(Request $request)
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);
        $periode_tes = $periode['monthName'] . ' ' . $periode['year'];

        // Base query to avoid duplication
        $baseQuery = PesertaKertosono::where('periode_id', $periode_pengetesan_id)
            ->whereNotIn('status_tes', [
                StatusTesKertosono::PRA_TES->value,
                StatusTesKertosono::TUNDA->value
            ])
            ->withHasilSistem();

        // Fetch data in one query to avoid multiple DB hits
        $pesertaData = $baseQuery->with(['siswa', 'akademik'])->get();

        // Compute overall stats
        $overallStats = $this->calculateStats($pesertaData);

        // Compute stats by gender
        $statsByGender = collect(JenisKelamin::cases())->mapWithKeys(function ($gender) use ($pesertaData) {
            return [$gender->value => $this->calculateStats(
                $pesertaData->where('siswa.jenis_kelamin', $gender)
            )];
        });

        return response()->json([
            'periode_tes' => $periode_tes,
            'overall' => $overallStats,
            'by_gender' => $statsByGender
        ]);
    }

    private function calculateStats($pesertaData)
    {
        $totalActive = $pesertaData->where('status_tes', StatusTesKediri::AKTIF->value)->count();

        $akademikCounts = $pesertaData->pluck('akademik')->map->count();
        $minAkademik = $akademikCounts->min() ?? 0;
        $maxAkademik = $akademikCounts->max() ?? 0;

        $userAkademikCount = $pesertaData->flatMap->akademik
            ->where('guru_id', Auth::id())
            ->count();

        $hasilSistemStats = $pesertaData->groupBy('hasil_sistem')->map->count();

        return [
            'total_active_peserta' => $totalActive,
            'min_akademik_per_peserta' => $minAkademik,
            'max_akademik_per_peserta' => $maxAkademik,
            'user_akademik_count' => $userAkademikCount,
            'hasil_sistem' => [
                'lulus' => $hasilSistemStats[HasilSistem::LULUS->getLabel()] ?? 0,
                'tidak_lulus' => $hasilSistemStats[HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()] ?? 0,
                'perlu_musyawarah' => $hasilSistemStats[HasilSistem::PERLU_MUSYAWARAH->getLabel()] ?? 0,
                'belum_pengetesan' => $hasilSistemStats[HasilSistem::BELUM_PENGETESAN->getLabel()] ?? 0,
            ],
        ];
    }
}
