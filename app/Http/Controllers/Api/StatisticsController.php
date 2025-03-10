<?php

namespace App\Http\Controllers\Api;

use App\Enums\HasilSistem;
use App\Enums\StatusTes;
use App\Enums\StatusTesKediri;
use App\Enums\StatusTesKertosono;
use App\Models\PesertaKediri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\JenisKelamin;
use App\Models\PesertaKertosono;

class StatisticsController extends Controller
{
    public function getStatistikPeserta($pesertaModel, $statusEnum)
    {
        $periode_pengetesan_id = getPeriodeTes();
        $periode = getYearAndMonthName($periode_pengetesan_id);
        $periode_tes = $periode['monthName'] . ' ' . $periode['year'];

        // Query utama
        $baseQuery = $pesertaModel::where('id_periode', $periode_pengetesan_id)
            ->whereIn('status_tes', [$statusEnum::AKTIF->value, $statusEnum::LULUS->value, $statusEnum::TIDAK_LULUS_AKHLAK->value, $statusEnum::TIDAK_LULUS_AKADEMIK->value, $statusEnum::PERLU_MUSYAWARAH->value])
            ->where('del_status', NULL)
            ->withHasilSistem();

        // Ambil data
        $pesertaData = $baseQuery->with(['siswa', 'akademik'])->get();

        // Hitung statistik
        $overallStats = $this->calculateStats($pesertaData);

        // Hitung statistik berdasarkan jenis kelamin
        $statsByGender = collect(JenisKelamin::cases())->mapWithKeys(function ($gender) use ($pesertaData) {
            return [$gender->getLabel() => $this->calculateStats(
                $pesertaData->where('siswa.jenis_kelamin', $gender)
            )];
        });

        $modelName =  $pesertaModel == PesertaKediri::class ? 'Kediri' : 'Kertosono';

        return response()->json([
            'message' => 'Data statistik tes '.$modelName.' berhasil diambil.',
            'data' => [
                'periode_tes' => $periode_tes,
                'overall' => $overallStats,
                'by_gender' => $statsByGender
            ]
        ], 200);
    }
    
    public function getStatistikKediri(Request $request)
    {
        return $this->getStatistikPeserta(PesertaKediri::class, StatusTesKediri::class);
    }

    public function getStatistikKertosono(Request $request)
    {
        return $this->getStatistikPeserta(PesertaKertosono::class, StatusTesKertosono::class);
    }

    private function calculateStats($pesertaData)
    {
        $totalActive = $pesertaData->where('status_tes', StatusTes::AKTIF->value)->count();

        // Create a collection of akademik counts per peserta
        $akademikCountsPerPeserta = $pesertaData->map(function($peserta) {
            return [
                'id' => $peserta->id_tes_santri,
                'count' => $peserta->akademik->count()
            ];
        });

        // Find minimum and maximum akademik counts
        $minAkademik = $akademikCountsPerPeserta->min('count') ?? 0;
        $maxAkademik = $akademikCountsPerPeserta->max('count') ?? 0;

        // Count peserta with min/max akademik
        $countPesertaMinAkademik = $akademikCountsPerPeserta->where('count', $minAkademik)->count();
        $countPesertaMaxAkademik = $akademikCountsPerPeserta->where('count', $maxAkademik)->count();

        // Compute user-specific akademik count
        $userAkademikCount = $pesertaData->flatMap(fn($peserta) => $peserta->akademik)
            ->where('guru_id', Auth::id())
            ->count();

        // Get hasil sistem statistics
        $hasilSistemStats = $pesertaData->countBy('hasil_sistem');

        return [
            'total_active_peserta' => $totalActive,
            'min_akademik_per_peserta' => $minAkademik,
            'max_akademik_per_peserta' => $maxAkademik,
            'count_peserta_with_min_akademik' => $countPesertaMinAkademik,
            'count_peserta_with_max_akademik' => $countPesertaMaxAkademik,
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
