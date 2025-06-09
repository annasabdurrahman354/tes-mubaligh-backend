<?php

namespace App\Http\Controllers\Api\PPWB;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class SiswaPPWBController extends Controller
{
    /**
     * Mengambil data siswa yang memiliki status ponpes aktif di ID 291.
     * Data diurutkan berdasarkan jenis kelamin, lalu nama lengkap.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 30);

        // 1. Query utama sekarang berbasis pada model Siswa
        $siswaQuery = QueryBuilder::for(Siswa::class)
            // 2. Filter utama: hanya siswa yang punya relasi statusPonpes dengan id_ponpes = 291
            ->whereHas('statusPonpes', function ($query) {
                $query->where('id_ponpes', 291);
            })
            ->allowedFilters($this->allowedFilters())
            ->with(['daerahSambung']); // Eager loading untuk mendapatkan nama daerah

        // Mengurutkan data
        $siswaQuery->orderBy('nama_lengkap', 'asc');

        // Menggunakan paginasi
        $siswaPaginator = $siswaQuery->paginate($perPage);

        // 3. Transformasi data menggunakan method baru transformSiswa
        $transformedSiswa = $siswaPaginator->getCollection()
            ->map(fn($siswa) => $this->transformSiswa($siswa));

        // Membuat response paginasi dengan data yang sudah ditransformasi
        $paginatedResponse = new LengthAwarePaginator(
            $transformedSiswa,
            $siswaPaginator->total(),
            $siswaPaginator->perPage(),
            $siswaPaginator->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginatedResponse);
    }

    /**
     * Mendefinisikan filter yang diizinkan untuk query.
     */
    private function allowedFilters(): array
    {
        return [
            AllowedFilter::partial('nama_lengkap'),
            AllowedFilter::exact('nis'),
            AllowedFilter::exact('nispn'),
            AllowedFilter::exact('jenis_kelamin'),
        ];
    }

    /**
     * Mengubah data Siswa menjadi format output yang sesuai dengan ekuivalensi.
     */
    private function transformSiswa(Siswa $siswa): array
    {
        // Menyusun alamat lengkap
        $alamatLengkap = sprintf(
            '%s, RT/RW : %s/%s,  %s, %s, %s, %s - %s',
            excelProper($siswa->alamat) ?? '-', // Menambahkan null coalescing untuk menghindari error jika null
            excelProper($siswa->rt) ?? '-',
            excelProper($siswa->rw) ?? '-',
            excelProper($siswa->desa_kel) ?? '-',
            excelProper($siswa->kecamatan) ?? '-',
            excelProper($siswa->kota_kab) ?? '-',
            excelProper($siswa->provinsi) ?? '-',
            excelProper($siswa->kode_pos) ?? '-'
        );

        return [
            // Data Diri Siswa
            'nispn' => $siswa->nispn,
            'nis' => $siswa->nis,
            'nama' => excelProper($siswa->nama_lengkap),
            'nama_ayah' => excelProper($siswa->nama_ayah),
            'nama_ibu' => excelProper($siswa->nama_ibu),
            'tempat_lahir' => $siswa->tempat_lahir,
            'tanggal_lahir' => $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('d-m-Y') : null,
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'umur' => $siswa->umur, // Menggunakan accessor dari model
            'rt' => $siswa->rt,
            'rw' => $siswa->rw,
            'desa_kel' => excelProper($siswa->desa_kel),
            'kecamatan' => excelProper($siswa->kecamatan),
            'kota_kab' => excelProper($siswa->kota_kab),
            'provinsi' => excelProper($siswa->provinsi),
            'kode_pos' => excelProper($siswa->kode_pos),
            'alamat_lengkap' => $alamatLengkap,
            'foto_siswa' => $siswa->urlFotoIdentitas, // Menggunakan accessor dari model

            // Informasi Akademik dan Keasramaan
            'pendidikan' => strtoupper($siswa->pendidikan),
            'kelompok_sambung' => excelProper($siswa->kelompok_sambung),
            'daerah_sambung' => excelProper($siswa->daerahSambung?->n_daerah), // Mengambil dari relasi
            'status_mondok' => $siswa->status_mondok->value,
        ];
    }

    /**
     * Mengupdate foto seorang siswa berdasarkan NISPN dalam satu fungsi.
     * Request berisi 'nispn' dan file 'photo'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhoto(Request $request)
    {
        // 1. Validasi input request
        $validator = Validator::make($request->all(), [
            'nispn' => 'required|string|exists:tb_personal_data,nispn',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Maksimum 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Cari siswa berdasarkan NISPN yang sudah divalidasi
        $siswa = Siswa::where('nispn', $request->input('nispn'))->first();
        // Jika siswa tidak ditemukan (meskipun seharusnya sudah dicek oleh 'exists')
        if (!$siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        }

        // Variabel untuk menampung nama file final yang akan disimpan ke DB
        $filenameForDb = $siswa->img_person; // Defaultnya adalah file yang sudah ada

        DB::beginTransaction();

        try {
            $oldFilename = $siswa->img_person;
            $newImageFile = $request->file('photo');

            // 3. Buat nama file yang unik berdasarkan nama siswa
            $namaLengkapClean = preg_replace('/[^a-zA-Z0-9\s]/', '', $siswa->nama_lengkap ?: 'unknown');
            $namaSlug = Str::slug($namaLengkapClean, '_');
            $timestamp = Carbon::now()->format('YmdHis');
            $randomString = Str::random(5);
            $extension = strtolower($newImageFile->getClientOriginalExtension());
            $newFilename = 'person_' . $namaSlug . '_' . $timestamp . '_' . $randomString . '.' . $extension;

            // 4. Upaya #1: Simpan menggunakan Storage::put ke disk 'ponpes_images'
            $uploadSuccess = false;
            $storagePutSuccess = false;

            try {
                if (Storage::disk('ponpes_images')->put($newFilename, $newImageFile->get())) {
                    Log::info("SUCCESS (Storage): File '{$newFilename}' disimpan untuk NISPN {$siswa->nispn}.");
                    // Hapus file lama dari disk 'ponpes_images' jika ada dan berbeda
                    if ($oldFilename && $oldFilename !== $newFilename && Storage::disk('ponpes_images')->exists($oldFilename)) {
                        Storage::disk('ponpes_images')->delete($oldFilename);
                        Log::info("SUCCESS (Storage): File lama '{$oldFilename}' dihapus untuk NISPN {$siswa->nispn}.");
                    }
                    // Jika sebelumnya tidak ada img_person (mungkin foto via Spatie), bersihkan Spatie
                    if (empty($oldFilename) && method_exists($siswa, 'clearMediaCollection')) {
                        $siswa->clearMediaCollection('siswa_foto_identitas');
                        Log::info("INFO (Spatie): Koleksi media 'siswa_foto_identitas' dibersihkan karena img_person sebelumnya kosong dan Storage::put berhasil untuk NISPN {$siswa->nispn}.");
                    }
                    $filenameForDb = $newFilename;
                    $uploadSuccess = true;
                    $storagePutSuccess = true;
                } else {
                    Log::warning("FAIL (Storage): Gagal menyimpan '{$newFilename}' via Storage::put untuk NISPN {$siswa->nispn}.");
                }
            } catch (Throwable $e) {
                Log::error("EXCEPTION (Storage): Gagal menyimpan '{$newFilename}' untuk NISPN {$siswa->nispn}. Error: " . $e->getMessage());
            }

            // 5. Upaya #2: Fallback ke Spatie Media Library (jika Storage::put gagal & tidak ada file lama di img_person)
            if (!$uploadSuccess && empty($oldFilename)) {
                Log::warning("FAIL (Storage) & empty oldFilename: Mencoba fallback ke Spatie Media Library untuk NISPN {$siswa->nispn}.");
                if (method_exists($siswa, 'addMediaFromRequest')) { // Pastikan trait Spatie ada
                    try {
                        // Ganti nama 'photo' menjadi 'new_person_photo' agar sesuai dengan logika Spatie sebelumnya jika ada
                        // atau pastikan Spatie menggunakan key 'photo'
                        $media = $siswa->addMediaFromRequest('photo') // Menggunakan key 'photo' langsung
                            ->preservingOriginal()
                            // ->usingName(pathinfo($newFilename, PATHINFO_FILENAME)) // Opsional: bisa menggunakan nama unik
                            // ->usingFileName($newFilename) // Opsional: bisa menggunakan nama unik
                            ->toMediaCollection('siswa_foto_identitas', 'public');

                        Log::info("SUCCESS (Spatie): Fallback berhasil, file '{$media->file_name}' disimpan untuk NISPN {$siswa->nispn}.");
                        $filenameForDb = null; // Kolom img_person dikosongkan jika Spatie berhasil
                        $uploadSuccess = true;
                        // Hapus file dari Storage jika ada yang sempat terupload dengan nama yang sama (kasus langka)
                        if (Storage::disk('ponpes_images')->exists($newFilename)) {
                            Storage::disk('ponpes_images')->delete($newFilename);
                        }
                    } catch (FileDoesNotExist | FileIsTooBig $e) {
                        Log::error("SPATIE FAIL: Unggahan Spatie gagal untuk NISPN {$siswa->nispn}. Error: " . $e->getMessage());
                    } catch (Throwable $e) {
                        Log::error("SPATIE EXCEPTION: Terjadi kesalahan saat unggah via Spatie untuk NISPN {$siswa->nispn}. Error: " . $e->getMessage());
                    }
                } else {
                    Log::warning("SPATIE SKIP: Model Siswa tidak memiliki method addMediaFromRequest untuk NISPN {$siswa->nispn}.");
                }
            }

            // 6. Logika Abort atau Gagal Total
            if (!$uploadSuccess) {
                if (!empty($oldFilename)) {
                    // Gagal unggah baru, tapi ada file lama, jadi pertahankan file lama
                    Log::warning("ABORT (Image Update): Gagal menyimpan file baru, file lama '{$oldFilename}' dipertahankan untuk NISPN {$siswa->nispn}.");
                    $filenameForDb = $oldFilename; // Pastikan filenameForDb adalah file lama
                } else {
                    // Gagal unggah baru dan tidak ada file lama
                    throw new \Exception("Gagal total menyimpan file gambar untuk NISPN {$siswa->nispn}. Primary storage dan fallback gagal.");
                }
            }

            // 7. Update kolom img_person di database
            $siswa->update(['img_person' => $filenameForDb]);

            // 8. Commit transaksi
            DB::commit();

            $siswa->refresh(); // Refresh model untuk mendapatkan URL foto terbaru dari accessor

            // 9. Berikan response sukses
            return response()->json([
                'message' => 'Foto berhasil diperbarui.',
                'nispn' => $siswa->nispn,
                'nama_lengkap' => $siswa->nama_lengkap,
                'foto_siswa' => $siswa->urlFotoIdentitas, // Accessor untuk mendapatkan URL foto terbaru
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Gagal update foto untuk NISPN {$request->input('nispn')}: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Terjadi kesalahan saat memperbarui foto.', 'error' => $e->getMessage()], 500);
        }
    }
}
