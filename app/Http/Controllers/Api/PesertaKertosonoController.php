<?php

namespace App\Http\Controllers\Api;

use App\Enums\HasilSistem;
use App\Filters\FiltersNamaOrCocard;
use App\Models\PesertaKertosono;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PesertaKertosonoController extends Controller
{
    /**
     * Get all Peserta Kertosono with filtering for kelompok, siswa's jenis_kelamin,
     * always sorting by siswa.nama (ascending), and filtering by periode_id '2021'.
     */
    public function index(Request $request)
    {
        $periode_pengetesan_id = getPeriodeTes();

        $pesertaQuery = QueryBuilder::for(PesertaKertosono::class)
            ->allowedFilters($this->allowedFilters())
            ->where('periode_id', $periode_pengetesan_id)
            ->tap(fn($query) => $query->withHasilSistem()) // Ensures scope is applied
            ->with(['siswa']) // Eager loading siswa for performance
            ->withCount('akademik'); // Count related akademik records


        // Apply filters based on request parameters
        if ($request->has('filter')) {
            $filterOption = $request->input('filter');

            switch ($filterOption) {
                case 'anda-simak':
                    $pesertaQuery->whereHas('akademik', function ($query) {
                        $query->where('guru_id', auth()->id());
                    });
                    break;

                case 'simak-terbanyak':
                    $maxAkademikCount = PesertaKertosono::where('periode_id', $periode_pengetesan_id)
                        ->withCount('akademik')
                        ->get()
                        ->max('akademik_count');

                    $pesertaQuery->having('akademik_count', $maxAkademikCount);
                    break;

                case 'simak-tersedikit':
                    $minAkademikCount = PesertaKertosono::where('periode_id', $periode_pengetesan_id)
                        ->withCount('akademik')
                        ->get()
                        ->min('akademik_count');

                    $pesertaQuery->having('akademik_count', $minAkademikCount);
                    break;

                case 'hasil-lulus':
                    $pesertaQuery->having('hasil_sistem', HasilSistem::LULUS->getLabel());
                    break;

                case 'hasil-tidak-lulus':
                    $pesertaQuery->having('hasil_sistem', HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel());
                    break;
            }
        } else {
            // Only take 30 records when there's no filter
            $pesertaQuery->take(30);
        }

        $peserta = $pesertaQuery->get()
            ->map(fn($peserta) => $this->transformPeserta($peserta, $request));

        return response()->json($peserta);
    }

    private function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('kelompok'),
            AllowedFilter::exact('siswa.jenis_kelamin'),
            AllowedFilter::custom('namaOrCocard', new FiltersNamaOrCocard),
        ];
    }

    private function transformPeserta($peserta, Request $request): array
    {
        $currentUserId = $request->user()->id;
        $tanggalLahir = $peserta->siswa->tanggal_lahir;
        $umur = Carbon::parse($tanggalLahir)->age;
        $pendidikan = $peserta->siswa->jurusan ? $peserta->siswa->pendidikan . ' - ' . $peserta->siswa->jurusan : $peserta->siswa->pendidikan;
        $telah_disimak = $peserta->akademik->contains(fn($akademik) => $akademik->guru_id === $currentUserId);

        return [
            'id' => $peserta->id,
            'periode_id' => $peserta->periode_id,
            'nispn' => $peserta->nispn,
            'nama_lengkap' => $peserta->siswa->nama_lengkap,
            'nama_panggilan' => $peserta->siswa->nama_panggilan,
            'jenis_kelamin' => $peserta->siswa->jenis_kelamin,
            'kelompok' => $peserta->kelompok,
            'nomor_cocard' => $peserta->nomor_cocard,
            'nis' => $peserta->siswa->nis,
            'nik' => $peserta->siswa->nik,
            'rfid' => $peserta->siswa->rfid,
            'kota_nama' => $peserta->siswa->kota->nama ?? null,
            'asal_pondok_nama' => $peserta->asalPondokNama ?? null,
            'asal_daerah_nama' => ucwords($peserta->asalDaerahNama) ?? null,
            'pendidikan' => $pendidikan,
            'status_mondok' => $peserta->siswa->status_mondok,
            'keahlian' => $peserta->siswa->keahlian ?? null,
            'hobi' => $peserta->siswa->hobi ?? null,
            'umur' => $umur,
            'nama_ayah' => formatNamaProper($peserta->siswa->nama_ayah),
            'riwayat_tes' => $peserta->riwayat_tes,
            'jumlah_penyimakan' => $peserta->count_lulus + $peserta->count_tidak_lulus,
            'count_lulus' => $peserta->count_lulus,
            'count_tidak_lulus' => $peserta->count_tidak_lulus,
            'hasil_sistem' => $peserta->hasil_sistem,
            'telah_disimak' => $telah_disimak,
            'foto_smartcard' => $peserta->siswa->urlFotoIdentitas,
            'akhlak' => $this->transformAkhlak($peserta->akhlak),
            'akademik' => $this->transformAkademik($peserta->akademik),
        ];
    }

    private function transformAkhlak($akhlak)
    {
        return $akhlak->map(fn($item) => [
            'id' => $item->id,
            'guru_id' => $item->guru_id,
            'guru_nama' => $item->guru->nama ?? null,
            'guru_foto' => $item->guru->getFilamentAvatarUrl(),
            'catatan' => $item->catatan,
            'created_at' => Carbon::parse($item->created_at)->translatedFormat('d F Y'),
        ]);
    }

    private function transformAkademik($akademik)
    {
        return $akademik->map(fn($item) => [
            'id' => $item->id,
            'guru_id' => $item->guru_id,
            'guru_nama' => $item->guru->nama ?? null,
            'guru_foto' => $item->guru->getFilamentAvatarUrl(),
            'penilaian' => $item->penilaian,
            'kekurangan_kelancaran' => $item->kekurangan_kelancaran,
            'kekurangan_keserasian' => $item->kekurangan_keserasian,
            'kekurangan_khusus' => $item->kekurangan_khusus,
            'kekurangan_tajwid' => $item->kekurangan_tajwid,
            'catatan' => $item->catatan,
            'rekomendasi_penarikan' => $item->rekomendasi_penarikan,
            'durasi_penilaian' => $item->durasi_penilaian,
            'created_at' => Carbon::parse($item->created_at)->translatedFormat('d F Y'),
        ]);
    }
}
