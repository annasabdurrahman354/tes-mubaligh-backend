<?php

namespace App\Http\Controllers\Api;

use App\Enums\HasilSistem;
use App\Enums\StatusTes;
use App\Filters\FiltersNamaOrCocard;
use App\Models\PesertaKertosono;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator; // Added for pagination

class PesertaKertosonoController extends Controller
{
    /**
     * Get all Peserta Kertosono with filtering and optimized sorting/pagination.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $periode_pengetesan_id = getPeriodeTes();
        $perPage = $request->input('per_page', 30); // Default items per page

        // More dynamic way to get table name
        $mainTable = (new PesertaKertosono())->getTable();

        $pesertaQuery = QueryBuilder::for(PesertaKertosono::class)
            // IMPORTANT: Select main table columns after join to avoid ambiguity
            ->select("{$mainTable}.*")
            ->allowedFilters($this->allowedFilters())
            ->where("{$mainTable}.id_periode", $periode_pengetesan_id)
            ->where("{$mainTable}.status_tes", StatusTes::AKTIF->value)
            ->where("{$mainTable}.del_status", NULL)
            // Apply scopes and load relationships efficiently
            ->tap(fn($query) => $query->withHasilSistem())
            ->with(['siswa', 'akademik', 'akhlak']) // Eager load all needed relations for transform
            ->withCount('akademik');

        // Apply conditional filters based on request parameters
        if ($request->has('filter')) {
            $filterOption = $request->input('filter');

            switch ($filterOption) {
                case 'anda-simak':
                    $pesertaQuery->whereHas('akademik', function ($query) {
                        $query->where('guru_id', Auth::id());
                    });
                    break;

                case 'simak-terbanyak':
                case 'simak-tersedikit':
                    // NOTE: Runs a separate query. Ensure indexes on akademik FK and peserta_kertosono filters.
                    $baseCountQuery = PesertaKertosono::where('id_periode', $periode_pengetesan_id)
                        ->where('status_tes', StatusTes::AKTIF->value)
                        ->where('del_status', NULL);

                    if ($filterOption === 'simak-terbanyak') {
                        $count = (clone $baseCountQuery)->withCount('akademik')->get()->max('akademik_count');
                        // Only apply filter if count is found (avoid issues with empty results)
                        if ($count !== null) {
                            $pesertaQuery->having('akademik_count', $count);
                        } else {
                            // Force no results if max count is null (e.g., no participants)
                            $pesertaQuery->whereRaw('1=0');
                        }
                    } else { // simak-tersedikit
                        $count = (clone $baseCountQuery)->withCount('akademik')->get()->min('akademik_count');
                        if ($count !== null) {
                            $pesertaQuery->having('akademik_count', $count);
                        } else {
                            $pesertaQuery->whereRaw('1=0');
                        }
                    }
                    break;

                case 'hasil-lulus':
                    // Assuming 'hasil_sistem' is calculated/aggregated, 'having' is correct
                    $pesertaQuery->having('hasil_sistem', HasilSistem::LULUS->getLabel());
                    break;

                case 'hasil-tidak-lulus':
                    $pesertaQuery->having('hasil_sistem', HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel());
                    break;
            }
        }

        $pesertaQuery->orderByRaw("CONVERT({$mainTable}.nomor_cocard, SIGNED) asc");

        // OPTIMIZED: Use Pagination consistently
        $pesertaPaginator = $pesertaQuery->paginate($perPage);

        // Transform the items included in the current page
        $transformedPeserta = $pesertaPaginator->getCollection()
            ->map(fn($peserta) => $this->transformPeserta($peserta, $request));

        // Return paginated response with transformed data
        $paginatedResponse = new LengthAwarePaginator(
            $transformedPeserta,
            $pesertaPaginator->total(),
            $pesertaPaginator->perPage(),
            $pesertaPaginator->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginatedResponse);
    }

    /**
     * Get a single peserta by RFID.
     */
    public function getByRFID(Request $request): \Illuminate\Http\JsonResponse
    {
        $rfid = $request->query('rfid');
        $periode_pengetesan_id = getPeriodeTes();

        if (!$rfid) {
            return response()->json(['message' => 'Parameter RFID diperlukan.'], 400);
        }

        $mainTable = (new PesertaKertosono())->getTable(); // Get table name dynamically

        // Find using whereHas on the relationship, load necessary data efficiently
        $peserta = PesertaKertosono::query()
            ->whereHas('siswa', fn($query) => $query->where('rfid', $rfid))
            ->where("{$mainTable}.id_periode", $periode_pengetesan_id)
            // Ensure data needed by transformPeserta is loaded
            ->with(['siswa', 'akademik', 'akhlak']) // Eager load relationships
            ->withCount('akademik') // Load count
            ->tap(fn($query) => $query->withHasilSistem()) // Apply scope for calculated fields
            ->first(); // Fetch the first matching record

        if (!$peserta) {
            return response()->json(['message' => 'Smartcard tidak terdata sebagai peserta pada periode ini.'], 404);
        }

        // Transform the single result
        $response = $this->transformPeserta($peserta, $request);

        return response()->json([
            'message' => 'Smartcard terdata sebagai peserta.',
            'data' => $response,
        ]);
    }

    /**
     * Allowed filters for QueryBuilder.
     */
    private function allowedFilters(): array
    {
        // Spatie's dot notation usually handles relationship filters well
        return [
            AllowedFilter::exact('kelompok'), // Assumes 'kelompok' is on PesertaKertosono table
            AllowedFilter::exact('siswa.jenis_kelamin'), // Filters through the 'siswa' relationship
            AllowedFilter::custom('namaOrCocard', new FiltersNamaOrCocard),
        ];
    }

    /**
     * Transform Peserta data for response.
     */
    private function transformPeserta($peserta, Request $request): array
    {
        // Ensure user is authenticated before accessing ID
        $currentUserId = Auth::id(); // More consistent with PesertaKediriController

        // Use null safe operator for potentially missing related data
        $tanggalLahir = $peserta->siswa?->tanggal_lahir;
        $umur = $tanggalLahir ? Carbon::parse($tanggalLahir)->age : null;

        $pendidikan = null;
        if ($peserta->siswa) {
            $pendidikan = $peserta->siswa->jurusan
                ? ($peserta->siswa->pendidikan . ' - ' . $peserta->siswa->jurusan)
                : $peserta->siswa->pendidikan;
        }

        // Check if the loaded akademik collection contains an entry for the current user
        // This relies on 'akademik' relationship being loaded via with()
        $telah_disimak = $peserta->relationLoaded('akademik')
            ? $peserta->akademik->contains('guru_id', $currentUserId)
            : false;

        // Find the specific akademik entry for the current user if it exists
        $akademik = $peserta->relationLoaded('akademik')
            ? $peserta->akademik->first(fn($akademik) => $akademik->guru_id === $currentUserId)
            : null;

        return [
            'id' => $peserta->id_tes_santri,
            'id_periode' => $peserta->id_periode,
            'nispn' => $peserta->nispn,
            'nama_lengkap' => excelProper($peserta->siswa?->nama_lengkap),
            'nama_panggilan' => excelProper($peserta->siswa?->nama_panggilan),
            'jenis_kelamin' => $peserta->siswa?->jenis_kelamin,
            'kelompok' => $peserta->kelompok,
            'nomor_cocard' => $peserta->nomor_cocard,
            'nis' => $peserta->siswa?->nis,
            'nik' => $peserta->siswa?->nik,
            'rfid' => $peserta->siswa?->rfid,
            'kota_nama' => $peserta->siswa?->kota?->nama, // Chained null-safe access
            'asal_pondok_nama' => $peserta->asalPondokWithDaerah, // Assuming Accessor exists
            'asal_daerah_nama' => excelProper($peserta->asalDaerah ?? ''), // Assuming Accessor exists
            'pendidikan' => $pendidikan,
            'status_mondok' => $peserta->siswa?->status_mondok,
            'keahlian' => $peserta->siswa?->keahlian,
            'hobi' => $peserta->siswa?->hobi,
            'umur' => $umur,
            'nama_ayah' => $peserta->siswa?->nama_ayah ? excelProper($peserta->siswa->nama_ayah) : null,
            'riwayat_tes' => $peserta->riwayat_tes, // Assuming Accessor/Attribute exists
            // Use the eager-loaded count for consistency
            'jumlah_penyimakan' => $peserta->akademik_count,
            // Keep original specific fields for backward compatibility
            'count_akademik_lulus' => $peserta->count_lulus,
            'count_akademik_tidak_lulus' => $peserta->count_tidak_lulus,
            'semua_kekurangan' => $peserta->allKekurangan,
            'perekomendasi' => $peserta->rekomendasiGuru,
            // Assuming Accessors/Attributes from scope/model exist
            'hasil_sistem' => $peserta->hasil_sistem,
            'telah_disimak' => $telah_disimak,
            'penilaian_anda' => $akademik ? $akademik->penilaian : null,
            'rekomendasi_anda' => $akademik ? $akademik->rekomendasi_penarikan : null,
            'foto_smartcard' => $peserta->siswa?->urlFotoIdentitas, // Assuming Accessor exists on Siswa
            // Transform loaded relations if they exist
            'akhlak' => $peserta->relationLoaded('akhlak') ? $peserta->akhlak->map->transform() : [],
            'akademik' => $peserta->relationLoaded('akademik') ? $peserta->akademik->map->transform() : [],
        ];
    }
}
