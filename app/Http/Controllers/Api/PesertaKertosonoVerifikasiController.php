<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusTes;
use App\Http\Controllers\Controller;
use App\Models\PesertaKertosono;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PesertaKertosonoVerifikasiController extends Controller
{
    /**
     * Display the specified resource.
     * Fetch PesertaKertosono along with its related Siswa data.
     */
    public function show($id_tes_santri)
    {
        $peserta = PesertaKertosono::with([
            'siswa' => function ($query) {
                // Eager load address relationships for Siswa if they exist
                $query->with(['provinsi', 'kota', 'kecamatan', 'kelurahan', 'daerahSambung']);
            },
            'ponpes' // Eager load ponpes relationship for PesertaKertosono
        ])->find($id_tes_santri);

        if (!$peserta) {
            return response()->json(['message' => 'Data Peserta tidak ditemukan'], 404);
        }

        if (!$peserta->siswa) {
            return response()->json(['message' => 'Data Siswa terkait tidak ditemukan'], 404);
        }

        // Prepare data structure for the frontend
        $data = [
            'peserta' => [
                'id_tes_santri' => $peserta->id_tes_santri,
                'id_ponpes' => $peserta->id_ponpes,
                // Add other PesertaKertosono fields if needed by the form
            ],
            'siswa' => [
                'nispn' => $peserta->siswa->nispn, // Needed for update reference
                'nik' => $peserta->siswa->nik, // Needed for update reference
                'jenis_kelamin' => $peserta->siswa->jenis_kelamin?->value, // Get the enum value
                'nama_lengkap' => $peserta->siswa->nama_lengkap,
                'nama_ayah' => $peserta->siswa->nama_ayah,
                'nama_ibu' => $peserta->siswa->nama_ibu,
                'tempat_lahir' => $peserta->siswa->tempat_lahir,
                // Format date to YYYY-MM-DD for HTML date input compatibility
                'tanggal_lahir' => $peserta->siswa->tanggal_lahir ? $peserta->siswa->tanggal_lahir->format('Y-m-d') : null,
                'alamat' => $peserta->siswa->alamat,
                'rt' => $peserta->siswa->rt,
                'rw' => $peserta->siswa->rw,
                'provinsi_id' => $peserta->siswa->provinsi_id,
                'kota_kab_id' => $peserta->siswa->kota_kab_id,
                'kecamatan_id' => $peserta->siswa->kecamatan_id,
                'desa_kel_id' => $peserta->siswa->desa_kel_id,
                'kode_pos' => $peserta->siswa->kode_pos,
                'hp' => $peserta->siswa->hp,
                'id_daerah_sambung' => $peserta->siswa->id_daerah_sambung,
                'kelompok_sambung' => $peserta->siswa->kelompok_sambung,
                'pendidikan' => $peserta->siswa->pendidikan,
                'status_mondok' => $peserta->siswa->status_mondok?->value, // Get the enum value
                // Add other Siswa fields if needed
            ],
        ];


        return response()->json($data);
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
            'id_ponpes' => 'required|integer|exists:m_ponpes,id_ponpes', // Ensure m_ponpes is your ponpes table name

            // Siswa fields
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'nama_lengkap' => 'required|string|max:100',
            'nama_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:100',
            'tempat_lahir' => 'required|string|max:50',
            'tanggal_lahir' => 'required|date_format:Y-m-d', // Match frontend format
            'alamat' => 'required|string|max:255',
            'rt' => 'required|string|max:3',
            'rw' => 'required|string|max:3',
            'provinsi_id' => 'required|integer|exists:m_provinsi,id_provinsi', // Ensure m_provinsi is your province table name
            'kota_kab_id' => 'required|integer|exists:m_kota_kab,id_kota_kab', // Ensure m_kota_kab is your city table name
            'kecamatan_id' => 'required|integer|exists:m_kecamatan,id_kecamatan', // Ensure m_kecamatan is your district table name
            'desa_kel_id' => 'required|integer|exists:m_desa_kel,id_desa_kel', // Ensure m_desa_kel is your village table name
            'kode_pos' => 'nullable|string|max:10',
            'hp' => 'nullable|string|max:20',
            'id_daerah_sambung' => 'required|integer|exists:m_daerah,id_daerah', // Ensure m_daerah is your daerah table name
            'kelompok_sambung' => 'required|string|max:50',
            'pendidikan' => ['required', Rule::in(['S1', 'S2', 'S3', 'D2', 'D3', 'D4', 'PAKET-C', 'SMA', 'SMK', 'SMP', 'SD', 'TK'])], // Correct field name and options
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
            ]);

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
                'kota_kab_id' => $request->input('kota_kab_id'),
                'kecamatan_id' => $request->input('kecamatan_id'),
                'desa_kel_id' => $request->input('desa_kel_id'),
                'kode_pos' => $request->input('kode_pos'),
                'hp' => $request->input('hp'),
                'id_daerah_sambung' => $request->input('id_daerah_sambung'),
                'kelompok_sambung' => $request->input('kelompok_sambung'),
                'pendidikan' => $request->input('pendidikan'),
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
}
