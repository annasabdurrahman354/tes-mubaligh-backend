<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusTes;
use App\Filters\FiltersNamaOrCocard;
use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\PesertaKertosono;
use App\Models\Provinsi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PesertaKertosonoVerifikasiController extends Controller
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
            ->leftJoin('tb_personal_data', "{$mainTable}.nispn", '=', 'tb_personal_data.nispn')
            ->allowedFilters($this->allowedFilters())
            ->where("{$mainTable}.id_periode", $periode_pengetesan_id)
            ->where("{$mainTable}.status_tes", StatusTes::PRA_TES->value)
            ->where("{$mainTable}.del_status", NULL)
            // Apply scopes and load relationships efficiently
            ->with(['siswa']); // Eager load all needed relations for transform

        // Apply Sorting (Priority: jenis_kelamin > kelompok > nomor_cocard)
        $pesertaQuery
            // OPTIMIZED: Order by joined column
            ->orderBy('tb_personal_data.jenis_kelamin', 'asc')
            ->orderByRaw('LOWER(tb_personal_data.nama_lengkap) ASC');

        // OPTIMIZED: Use Pagination consistently
        $pesertaPaginator = $pesertaQuery->paginate($perPage);

        // Transform the items included in the current page
        $transformedPeserta = $pesertaPaginator->getCollection()
            ->map(fn($peserta) => $this->transformPeserta($peserta));

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
     * Display the specified resource.
     * Fetch PesertaKertosono along with its related Siswa data.
     */
    public function show($id_tes_santri)
    {
        $peserta = PesertaKertosono::with(['siswa'])->find($id_tes_santri);

        if (!$peserta) {
            return response()->json(['message' => 'Data Peserta tidak ditemukan'], 404);
        }

        if (!$peserta->siswa) {
            return response()->json(['message' => 'Data Siswa terkait tidak ditemukan'], 404);
        }

        return response()->json($this->transformPeserta($peserta));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id_tes_santri)
    {
        $peserta = PesertaKertosono::find($id_tes_santri);

        if (!$peserta) {
            return response()->json(['message' => 'Data Peserta tidak ditemukan'], 404);
        }

        $siswa = Siswa::where('nispn', $peserta->nispn)->first();

        if (!$siswa) {
            return response()->json(['message' => 'Data Siswa terkait tidak ditemukan'], 404);
        }

        // Validation Rules
        $validator = Validator::make($request->all(), [
            // PesertaKertosono fields
            'id_ponpes' => 'required|integer|exists:tb_ponpes,id_ponpes', // Ensure m_ponpes is your ponpes table name

            // Siswa fields
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'nama_lengkap' => 'required|string',
            'nama_ayah' => 'nullable|string',
            'nama_ibu' => 'nullable|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date_format:Y-m-d', // Match frontend format
            'alamat' => 'required|string',
            'rt' => 'required|string',
            'rw' => 'required|string',
            'provinsi_id' => 'required|integer|exists:tb_provinsi,id_provinsi', // Ensure m_provinsi is your province table name
            'kota_kab_id' => 'required|integer|exists:tb_kota_kab,id_kota_kab', // Ensure m_kota_kab is your city table name
            'kecamatan_id' => 'required|integer|exists:tb_kecamatan,id_kecamatan', // Ensure m_kecamatan is your district table name
            'desa_kel_id' => 'required|integer|exists:tb_desa_kel,id_desa_kel', // Ensure m_desa_kel is your village table name
            'kode_pos' => 'nullable|string',
            'hp' => 'nullable|string',
            'id_daerah_sambung' => 'required|integer|exists:tb_daerah,id_daerah', // Ensure m_daerah is your daerah table name
            'kelompok_sambung' => 'required|string',
            'pendidikan' => ['required', Rule::in(['S1', 'S2', 'S3', 'D2', 'D3', 'D4', 'PAKET-C', 'SMA', 'SMK', 'SMP', 'SD', 'TK'])], // Correct field name and options
            'jurusan' => 'nullable', // Correct field name and options
            'status_mondok' => ['required', Rule::in(['person', 'kiriman', 'pelajar'])],
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Use Transaction for Atomic Update
        try {
            DB::beginTransaction();

            // Update PesertaKertosono
            $peserta->update([
                'id_ponpes' => $request->input('id_ponpes'),
                'status_tes' => StatusTes::AKTIF->value,
            ]);

            $provinsi = Provinsi::find($request->input('provinsi_id'));
            $kota_kab = Kota::find($request->input('kota_kab_id'));
            $kecamatan = Kecamatan::find($request->input('kecamatan_id'));
            $desa_kel = Kelurahan::find($request->input('desa_kel_id'));

            // Update Siswa (use NIK or NISPN as the key based on your primary logic)
            // Assuming NIK is the primary key as per your model
            $siswa->update([
                'jenis_kelamin' => $request->input('jenis_kelamin'),
                'nama_lengkap' => $request->input('nama_lengkap'),
                'nama_ayah' => $request->input('nama_ayah'),
                'nama_ibu' => $request->input('nama_ibu'),
                'tempat_lahir' => $request->input('tempat_lahir'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'alamat' => $request->input('alamat'),
                'rt' => $request->input('rt'),
                'rw' => $request->input('rw'),
                'provinsi_id' => $request->input('provinsi_id'),
                'provinsi' => $provinsi->nama,
                'kota_kab_id' => $request->input('kota_kab_id'),
                'kota_kab' => $kota_kab->nama,
                'kecamatan_id' => $request->input('kecamatan_id'),
                'kecamatan' => $kecamatan->nama,
                'desa_kel_id' => $request->input('desa_kel_id'),
                'desa_kel' => $desa_kel->nama,
                'kode_pos' => $request->input('kode_pos'),
                'hp' => $request->input('hp'),
                'id_daerah_sambung' => $request->input('id_daerah_sambung'),
                'kelompok_sambung' => $request->input('kelompok_sambung'),
                'pendidikan' => $request->input('pendidikan'),
                'jurusan' => $request->input('jurusan'),
                'status_mondok' => $request->input('status_mondok'),
            ]);

            DB::commit();

            // Fetch the updated data again to return
            $updatedData = $this->show($id_tes_santri); // Re-use the show method
            return $updatedData; // Return the updated data

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Peserta Kertosono: ' . $e->getMessage()); // Log the error
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
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
    private function transformPeserta($peserta): array
    {
        // Use null safe operator for potentially missing related data
        $tanggalLahir = $peserta->siswa?->tanggal_lahir;
        $umur = $tanggalLahir ? Carbon::parse($tanggalLahir)->age : null;

        $provinsi = Provinsi::where('nama', $peserta->siswa?->provinsi)
            ->first();

        $kota_kab = null;
        $kecamatan = null;
        $desa_kel = null;

        if ($provinsi) {
            $kota_kab = Kota::where('nama', $peserta->siswa?->kota_kab)
                ->where('provinsi_id', $provinsi->id_provinsi)
                ->first();
        }

        if ($kota_kab) {
            $kecamatan = Kecamatan::where('nama', $peserta->siswa?->kecamatan)
                ->where('kota_kab_id', $kota_kab->id_kota_kab)
                ->first();
        }

        if ($kecamatan) {
            $desa_kel = Kelurahan::where('nama', $peserta->siswa?->desa_kel ?? '')
                ->where('kecamatan_id', $kecamatan->id_kecamatan)
                ->first();
        }

        return [
            'id_tes_santri' => $peserta->id_tes_santri,
            'id_periode' => $peserta->id_periode,
            'id_ponpes' => $peserta->id_ponpes,
            'nispn' => $peserta->nispn,
            'nama_lengkap' => excelProper($peserta->siswa?->nama_lengkap),
            'nama_panggilan' => excelProper($peserta->siswa?->nama_panggilan),
            'jenis_kelamin' => $peserta->siswa?->jenis_kelamin,
            'kelompok' => $peserta->kelompok,
            'nomor_cocard' => $peserta->nomor_cocard,
            'nis' => $peserta->siswa?->nis,
            'nik' => $peserta->siswa?->nik,
            'rfid' => $peserta->siswa?->rfid,
            'nama_ibu' => excelProper($peserta->siswa->nama_ibu),
            'tempat_lahir' => $peserta->siswa->tempat_lahir,
            'tanggal_lahir' => $peserta->siswa->tanggal_lahir ? $peserta->siswa->tanggal_lahir->format('Y-m-d') : null,
            'alamat' => $peserta->siswa->alamat,
            'rt' => $peserta->siswa->rt,
            'rw' => $peserta->siswa->rw,
            'provinsi' => $provinsi?->nama,
            'provinsi_id' => $provinsi?->id_provinsi,
            'kota_kab' => $kota_kab?->nama,
            'kota_kab_id' => $kota_kab?->id_kota_kab,
            'kecamatan' => $kecamatan?->nama,
            'kecamatan_id' => $kecamatan?->id_kecamatan,
            'desa_kel' => $desa_kel?->nama,
            'desa_kel_id' => $desa_kel?->id_desa_kel,
            'kode_pos' => $peserta->siswa->kode_pos,
            'kota_nama' => $kota_kab->nama ?? $peserta->siswa->kota_kab, // Chained null-safe access
            'hp' => $peserta->siswa->hp,
            'id_daerah_sambung' => $peserta->siswa->id_daerah_sambung,
            'kelompok_sambung' => $peserta->siswa->kelompok_sambung,
            'asal_pondok_nama' => $peserta->asalPondokWithDaerah, // Assuming Accessor exists
            'asal_daerah_nama' => ucwords(strtolower($peserta->asalDaerah ?? '')), // Assuming Accessor exists
            'pendidikan' => $peserta->siswa->pendidikan,
            'jurusan' => $peserta->siswa->jurusan,
            'status_mondok' => $peserta->siswa?->status_mondok,
            'keahlian' => $peserta->siswa?->keahlian,
            'hobi' => $peserta->siswa?->hobi,
            'umur' => $umur,
            'nama_ayah' => $peserta->siswa?->nama_ayah ? excelProper($peserta->siswa->nama_ayah) : null,
            'riwayat_tes' => $peserta->riwayat_tes, // Assuming Accessor/Attribute exists
            'foto_smartcard' => $peserta->siswa?->urlFotoIdentitas, // Assuming Accessor exists on Siswa
        ];
    }
}
