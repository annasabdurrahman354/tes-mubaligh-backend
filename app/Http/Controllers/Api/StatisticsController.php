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
        $baseQuery = $pesertaModel::where('periode_id', $periode_pengetesan_id)
            ->whereNotIn('status_tes', [
                $statusEnum::PRA_TES->value,
                $statusEnum::TUNDA->value
            ])
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

        // Hitung jumlah akademik dengan lebih optimal
        $akademikCounts = $pesertaData->pluck('akademik')->map(fn($akademik) => count($akademik));
        $minAkademik = $akademikCounts->min() ?? 0;
        $maxAkademik = $akademikCounts->max() ?? 0;

        // Hitung jumlah peserta yang memiliki akademik = minAkademik
        $countPesertaMinAkademik = $pesertaData->filter(fn($peserta) => count($peserta->akademik) === $minAkademik)->count();

        // Hitung jumlah peserta yang memiliki akademik = maxAkademik
        $countPesertaMaxAkademik = $pesertaData->filter(fn($peserta) => count($peserta->akademik) === $maxAkademik)->count();

        // Hitung akademik yang berkaitan dengan user yang login
        $userAkademikCount = $pesertaData->flatMap(fn($peserta) => $peserta->akademik)
            ->where('guru_id', Auth::id())
            ->count();

        // Hitung hasil sistem tanpa perlu groupBy manual
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
