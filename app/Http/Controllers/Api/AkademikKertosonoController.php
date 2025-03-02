<?php

namespace App\Http\Controllers\Api;

use App\Models\AkademikKertosono;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class AkademikKertosonoController extends Controller
{
    /**
     * Create or update an AkademikKertosono entry by peserta_id and guru_id.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tes_santri_id' => 'required|exists:tb_tes_santri,id_tes_santri',
            'penilaian' => 'required|string',
            'kekurangan_tajwid' => 'nullable',
            'kekurangan_khusus' => 'nullable',
            'kekurangan_keserasian' => 'nullable',
            'kekurangan_kelancaran' => 'nullable',
            'catatan' => 'nullable|string',
            'rekomendasi_penarikan' => 'nullable|boolean',
            'durasi_penilaian' => 'required|numeric|min:0',
        ], [
            'tes_santri_id.required' => 'ID tes santri wajib diisi.',
            'tes_santri_id.exists' => 'ID tes santri tidak ditemukan dalam database.',
            'penilaian.required' => 'Penilaian wajib diisi.',
            'penilaian.string' => 'Penilaian harus berupa teks.',
            'catatan.string' => 'Catatan harus berupa teks.',
            'rekomendasi_penarikan.boolean' => 'Rekomendasi penarikan harus berupa nilai benar atau salah.',
            'durasi_penilaian.required' => 'Durasi penilaian wajib diisi.',
            'durasi_penilaian.numeric' => 'Durasi penilaian harus berupa angka.',
            'durasi_penilaian.min' => 'Durasi penilaian tidak boleh kurang dari 0.',
        ]);

        $validated['guru_id'] = Auth::id();

        $akademikKertosono = AkademikKertosono::updateOrCreate(
            [
                'tes_santri_id' => $validated['tes_santri_id'],
                'guru_id' => $validated['guru_id'],
            ],
            $validated
        );

        return response()->json(["message" => "Nilai akademik berhasil disimpan.", "data" => $akademikKertosono], 200);
    }

    /**
     * List AkademikKertosono entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkademikKertosono::class)
            ->allowedFilters(['tes_santri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akademik berhasil diambil.", "data" => $data], 200);
    }
}
