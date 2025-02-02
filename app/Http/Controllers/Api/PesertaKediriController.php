<?php

namespace App\Http\Controllers\Api;

use App\Enums\HasilSistem;
use App\Filters\FiltersNamaOrCocard;
use App\Models\PesertaKediri;
use App\Models\PesertaKertosono;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PesertaKediriController extends Controller
{
    /**
     * Get all Peserta Kediri with filtering for kelompok, siswa's jenis_kelamin,
     * always sorting by siswa.nama (ascending), and filtering by periode_id.
     */
    public function index(Request $request)
    {
        $periode_pengetesan_id = getPeriodeTes();

        $pesertaQuery = QueryBuilder::for(PesertaKediri::class)
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
        }

        $peserta = $pesertaQuery->take(30)->get()
            ->map(fn($peserta) => $this->transformPeserta($peserta, $request));

        return response()->json($peserta);
    }

    public function getByNfc(Request $request)
    {
        $nfc = $request->query('nfc');
        $periode_pengetesan_id = getPeriodeTes();

        $peserta = PesertaKediri::whereHas('siswa', fn($query) => $query->where('rfid', $nfc))
            ->join('siswa', 'peserta_kediri.siswa_id', '=', 'siswa.id')
            ->where('periode_id', $periode_pengetesan_id)
            ->select($this->selectFields())
            ->first();

        if (!$peserta) {
            return response()->json(['message' => 'Smartcard tidak terdata sebagai peserta.'], 404);
        }

        $response = $this->transformPeserta($peserta, $request->user()->id);

        return response()->json([
            'message' => 'Smartcard terdata sebagai peserta.',
            'data' => $response,
        ]);
    }

    /**
     * Allowed filters for QueryBuilder.
     */
    private function allowedFilters()
    {
        return [
            AllowedFilter::exact('kelompok'),
            AllowedFilter::exact('siswa.jenis_kelamin'),
            AllowedFilter::custom('namaOrCocard', new FiltersNamaOrCocard),
        ];
    }

    /**
     * Transform Peserta data for response.
     */
    private function transformPeserta($peserta,  Request $request)
    {
        $currentUserId = $request->user()->id;
        $tanggalLahir = $peserta->siswa->tanggal_lahir ?? null;
        $umur = $tanggalLahir ? Carbon::parse($tanggalLahir)->age : null;
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
            'jumlah_penyimakan' => $peserta->akademik_count,
            'total_poin_akhlak' => $peserta->totalPoinAkhlak,
            'avg_nilai_makna' => $peserta->avg_nilai_makna,
            'avg_nilai_keterangan' => $peserta->avg_nilai_keterangan,
            'avg_nilai_penjelasan' => $peserta->avg_nilai_penjelasan,
            'avg_nilai_pemahaman' => $peserta->avg_nilai_pemahaman,
            'avg_nilai' => $peserta->avg_nilai,
            'hasil_sistem' => $peserta->hasil_sistem,
            'telah_disimak' => $telah_disimak,
            'foto_smartcard' => $peserta->foto_smartcard,
            'akhlak' => $this->transformAkhlak($peserta->akhlak),
            'akademik' => $this->transformAkademik($peserta->akademik),
        ];
    }

    /**
     * Transform Akhlak data.
     */
    private function transformAkhlak($akhlakCollection)
    {
        return $akhlakCollection->map(fn($akhlak) => [
            'id' => $akhlak->id,
            'guru_id' => $akhlak->guru_id,
            'guru_nama' => $akhlak->guru->nama ?? null,
            'guru_foto' => $akhlak->guru->getFilamentAvatarUrl(),
            'catatan' => $akhlak->catatan,
            'poin' => $akhlak->poin,
            'created_at' => Carbon::parse($akhlak->created_at)->translatedFormat('d F Y'),
        ]);
    }

    /**
     * Transform Akademik data.
     */
    private function transformAkademik($akademikCollection)
    {
        return $akademikCollection->map(fn($akademik) => [
            'id' => $akademik->id,
            'guru_id' => $akademik->guru_id,
            'guru_nama' => $akademik->guru->nama ?? null,
            'guru_foto' => $akademik->guru->getFilamentAvatarUrl(),
            'nilai_makna' => $akademik->nilai_makna,
            'nilai_keterangan' => $akademik->nilai_keterangan,
            'nilai_penjelasan' => $akademik->nilai_penjelasan,
            'nilai_pemahaman' => $akademik->nilai_pemahaman,
            'catatan' => $akademik->catatan,
            'created_at' => Carbon::parse($akademik->created_at)->translatedFormat('d F Y'),
        ]);
    }
}
