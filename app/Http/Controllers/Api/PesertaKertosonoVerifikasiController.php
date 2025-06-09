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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

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
     * Handles file upload with specific fallback logic:
     * 1. Tries saving to 'ponpes_images' disk via Storage::put().
     * 2. If step 1 fails AND the original 'img_person' was null/empty,
     * tries saving using Spatie Media Library to 'siswa_foto_identitas'.
     * 3. If step 1 fails AND original 'img_person' had a value, it ABORTS the image update
     * (no fallback, no deletion of the old file).
     * 4. Updates Siswa and PesertaKertosono data.
     * 5. Deletes the old image from 'ponpes_images' ONLY if Storage::put() succeeded.
     */
    public function update(Request $request, $id_tes_santri)
    {
        // 1. Find Peserta record
        $peserta = PesertaKertosono::find($id_tes_santri);
        if (!$peserta) {
            return response()->json(['message' => 'Data Peserta tidak ditemukan'], 404);
        }

        // 2. Find associated Siswa record
        $siswa = Siswa::where('nispn', $peserta->nispn)->first();
        if (!$siswa) {
            return response()->json(['message' => 'Data Siswa terkait tidak ditemukan'], 404);
        }

        // 3. Validation Rules
        $validator = Validator::make($request->all(), [
            // PesertaKertosono fields
            'id_ponpes' => 'required|integer|exists:tb_ponpes,id_ponpes',
            // Siswa fields
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'nama_lengkap' => 'required|string|max:255',
            'nama_ayah' => 'nullable|string',
            'nama_ibu' => 'nullable|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date_format:Y-m-d',
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
            'pendidikan' => ['required', Rule::in(['S1', 'S2', 'S3', 'D2', 'D3', 'D4', 'PAKET-C', 'SMA', 'SMK', 'SMP', 'SD', 'TK', 'Tidak Sekolah', 'Lainnya'])],
            'jurusan' => 'nullable|string',
            'status_mondok' => ['required', Rule::in(['person', 'kiriman', 'pelajar'])],
            // File upload validation
            'new_person_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 4. Initialize variables
        $filenameForDb = $siswa->img_person; // Default: keep existing filename
        $oldFilename = $siswa->img_person;   // Store original filename
        $uploadSuccess = false;             // Did ANY upload method succeed?
        $storagePutSuccess = false;         // Did Storage::put specifically succeed?
        $newFilenameGenerated = null;
        $storagePutAttemptedFile = null;    // For rollback cleanup if put() saved then failed later

        // 5. Start Database Transaction
        DB::beginTransaction();

        try {
            // 6. Process Image Upload (if new file provided)
            if ($request->hasFile('new_person_photo')) {
                $newImageFile = $request->file('new_person_photo');

                // 6a. Generate a unique filename
                $namaLengkapClean = preg_replace('/[^a-zA-Z\s]/', '', $request->input('nama_lengkap') ?: 'unknown');
                $namaSlug = Str::slug($namaLengkapClean, '_');
                $timestamp_ = Carbon::now()->format('YmdHis');
                $randomString = Str::random(5);
                $extension = strtolower($newImageFile->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    throw new \Exception("Ekstensi file tidak valid: $extension");
                }
                $newFilenameGenerated = 'person_' . $namaSlug . '_' . $timestamp_ . '_' . $randomString . '.' . $extension;
                $storagePutAttemptedFile = $newFilenameGenerated; // Mark for potential cleanup

                // 6b. --- Attempt 1: Save using Storage::put to 'ponpes_images' disk ---
                Log::info("[Peserta Update ID:{$id_tes_santri}] Attempting Storage::put for '{$newFilenameGenerated}' on disk 'ponpes_images'.");
                $storagePutPath = false;
                try {
                    $storagePutPath = Storage::disk('ponpes_images')->put(
                        $newFilenameGenerated,
                        $newImageFile->get() // Get file content
                    );

                    if ($storagePutPath !== false && is_string($storagePutPath)) {
                        $filenameForDb = $newFilenameGenerated; // Update filename for DB
                        $uploadSuccess = true;                  // Mark overall success
                        $storagePutSuccess = true;              // Mark Storage::put success specifically
                        Log::info("[Peserta Update ID:{$id_tes_santri}] SUCCESS: Storage::put saved '{$newFilenameGenerated}'. Path: {$storagePutPath}");
                    } else {
                        Log::warning("[Peserta Update ID:{$id_tes_santri}] FAIL: Storage::put failed for '{$newFilenameGenerated}' on disk 'ponpes_images'. Return: " . json_encode($storagePutPath));
                        $storagePutAttemptedFile = null; // Don't try to delete this on rollback
                    }
                } catch (\Exception $e) {
                    Log::error("[Peserta Update ID:{$id_tes_santri}] EXCEPTION during Storage::put for '{$newFilenameGenerated}': " . $e->getMessage());
                    $storagePutAttemptedFile = null; // Don't try to delete this on rollback
                    // $uploadSuccess and $storagePutSuccess remain false
                }

                // 6c. --- Attempt 2: Spatie Fallback (CONDITIONAL) ---
                // Only attempt Spatie if Storage::put failed AND there was NO existing img_person file
                if (!$uploadSuccess && empty($oldFilename)) {
                    Log::info("[Peserta Update ID:{$id_tes_santri}] Storage::put failed AND old filename was empty. Attempting Spatie Media Library fallback.");
                    try {
                        if (!method_exists($siswa, 'addMediaFromRequest')) {
                            throw new \Exception("Siswa model does not use InteractsWithMedia trait.");
                        }
                        // Clear previous media if needed: $siswa->clearMediaCollection('siswa_foto_identitas');
                        $media = $siswa->addMediaFromRequest('new_person_photo')
                            ->preservingOriginal()
                            ->usingName(pathinfo($newFilenameGenerated, PATHINFO_FILENAME))
                            ->usingFileName($newFilenameGenerated)
                            ->toMediaCollection('siswa_foto_identitas', 'public');

                        $filenameForDb = null; // Spatie succeeded, nullify img_person
                        $uploadSuccess = true; // Mark overall success
                        // $storagePutSuccess remains false
                        Log::info("[Peserta Update ID:{$id_tes_santri}] SUCCESS: Spatie Media Library saved '{$media->file_name}'.");

                    } catch (FileDoesNotExist $e) {
                        Log::error("[Peserta Update ID:{$id_tes_santri}] SPATIE FAIL: FileDoesNotExist. " . $e->getMessage());
                    } catch (FileIsTooBig $e) {
                        Log::error("[Peserta Update ID:{$id_tes_santri}] SPATIE FAIL: FileIsTooBig. " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::error("[Peserta Update ID:{$id_tes_santri}] EXCEPTION during Spatie Media Library upload: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    }
                }
                // 6d. --- Handle case where Storage::put failed BUT an old file existed ---
                elseif (!$uploadSuccess && !empty($oldFilename)) {
                    // Storage::put failed, but an old file existed. Per requirements, ABORT image update.
                    Log::warning("[Peserta Update ID:{$id_tes_santri}] Storage::put failed for '{$newFilenameGenerated}', but '{$oldFilename}' already exists. Aborting image update attempt for this request. Original image will be kept.");
                    // Keep original filename for DB update
                    $filenameForDb = $oldFilename;
                    // $uploadSuccess remains false
                    // $storagePutSuccess remains false
                    // No Spatie attempt is made, no old file deletion will occur later.
                }

                // 6e. --- Final Check: Throw error only if upload was expected but failed ---
                // Error if: new file was uploaded, had no previous image, and BOTH methods failed.
                if (!$uploadSuccess && empty($oldFilename)) {
                    throw new \Exception("Gagal menyimpan file gambar baru. Primary storage (Storage::put) dan fallback (Spatie Media Library) keduanya gagal. Periksa log dan permissions.");
                }
                // If $oldFilename existed and Storage::put failed, we simply skip the image update without error here.

            } // --- End of image processing block ---

            // 7. Prepare data for Siswa update
            $provinsi = Provinsi::find($request->input('provinsi_id'));
            $kota_kab = Kota::find($request->input('kota_kab_id'));
            $kecamatan = Kecamatan::find($request->input('kecamatan_id'));
            $desa_kel = Kelurahan::find($request->input('desa_kel_id'));

            $siswaDataToUpdate = [
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
                'provinsi' => $provinsi?->nama,
                'kota_kab_id' => $request->input('kota_kab_id'),
                'kota_kab' => $kota_kab?->nama,
                'kecamatan_id' => $request->input('kecamatan_id'),
                'kecamatan' => $kecamatan?->nama,
                'desa_kel_id' => $request->input('desa_kel_id'),
                'desa_kel' => $desa_kel?->nama,
                'kode_pos' => $request->input('kode_pos'),
                'hp' => $request->input('hp'),
                'id_daerah_sambung' => $request->input('id_daerah_sambung'),
                'kelompok_sambung' => $request->input('kelompok_sambung'),
                'pendidikan' => $request->input('pendidikan'),
                'jurusan' => $request->input('jurusan'),
                'status_mondok' => $request->input('status_mondok'),
                // Assign filename: old, new (Storage::put), or null (Spatie) based on logic above
                'img_person' => $filenameForDb,
            ];

            // 8. Update PesertaKertosono record
            $peserta->update([
                'id_ponpes' => $request->input('id_ponpes'),
                'status_tes' => StatusTes::AKTIF->value,
            ]);

            // 9. Update Siswa record
            $siswa->update($siswaDataToUpdate);

            // 10. Delete old image file from 'ponpes_images' disk ONLY if Storage::put() succeeded
            // Condition: New file uploaded, old filename existed, Storage::put() specifically succeeded, and filename changed
            if ($request->hasFile('new_person_photo') && $oldFilename && $storagePutSuccess && $filenameForDb !== $oldFilename) {
                Log::info("[Peserta Update ID:{$id_tes_santri}] Storage::put succeeded. Attempting to delete old file '{$oldFilename}' from 'ponpes_images'.");
                if (Storage::disk('ponpes_images')->exists($oldFilename)) {
                    try {
                        if (Storage::disk('ponpes_images')->delete($oldFilename)) {
                            Log::info("[Peserta Update ID:{$id_tes_santri}] SUCCESS: Deleted old file '{$oldFilename}'.");
                        } else {
                            Log::warning("[Peserta Update ID:{$id_tes_santri}] FAIL: Storage::delete returned false for old file '{$oldFilename}'.");
                        }
                    } catch (Throwable $deleteError) {
                        Log::error("[Peserta Update ID:{$id_tes_santri}] EXCEPTION during old file deletion '{$oldFilename}': " . $deleteError->getMessage());
                    }
                } else {
                    Log::warning("[Peserta Update ID:{$id_tes_santri}] Old file '{$oldFilename}' not found on 'ponpes_images' disk for deletion.");
                }
            } else if ($request->hasFile('new_person_photo') && $oldFilename && !$storagePutSuccess) {
                Log::info("[Peserta Update ID:{$id_tes_santri}] Skipping deletion of old file '{$oldFilename}' because Storage::put did not succeed or image update was aborted.");
            }

            // 11. Commit Transaction
            DB::commit();
            Log::info("[Peserta Update ID:{$id_tes_santri}] Update committed successfully.");

            // 12. Fetch updated data and return response
            $updatedPeserta = PesertaKertosono::with(['siswa'])->find($id_tes_santri);
            if (!$updatedPeserta) {
                Log::error("[Peserta Update ID:{$id_tes_santri}] Committed successfully but failed to retrieve updated record.");
                return response()->json(['message' => 'Data tidak ditemukan setelah pembaruan.'], 404);
            }
            return response()->json($this->transformPeserta($updatedPeserta));

        } catch (Throwable $e) {
            // 13. Handle Exceptions and Rollback
            DB::rollBack();
            Log::error("[Peserta Update ID:{$id_tes_santri}] TRANSACTION ROLLED BACK.");

            // 13a. Attempt cleanup for Storage::put file if it was potentially saved
            if ($storagePutAttemptedFile && Storage::disk('ponpes_images')->exists($storagePutAttemptedFile)) {
                Log::warning("[Peserta Update ID:{$id_tes_santri}] Rollback: Attempting to delete potentially orphaned file '{$storagePutAttemptedFile}' from Storage::put.");
                try {
                    Storage::disk('ponpes_images')->delete($storagePutAttemptedFile);
                    Log::info("[Peserta Update ID:{$id_tes_santri}] Rollback: Orphaned file '{$storagePutAttemptedFile}' deleted.");
                } catch (Throwable $cleanupError) {
                    Log::error("[Peserta Update ID:{$id_tes_santri}] Rollback: Failed to delete orphaned file '{$storagePutAttemptedFile}': " . $cleanupError->getMessage());
                }
            }

            // 13b. Log primary error
            Log::error("[Peserta Update ID:{$id_tes_santri}] Update failed: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());

            // 13c. Return error response
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    } // End of update function

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
