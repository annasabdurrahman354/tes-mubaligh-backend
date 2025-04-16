<?php

namespace App\Http\Controllers\Api;

use App\Models\AkademikKediri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class AkademikKediriController extends Controller
{
    /**
     * Create or update an AkademikKediri entry by peserta_id and guru_id.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tes_santri_id' => 'required|exists:tb_tes_santri,id_tes_santri',
            'nilai_makna' => 'required|numeric|min:0|max:100',
            'nilai_keterangan' => 'required|numeric|min:0|max:100',
            'nilai_penjelasan' => 'required|numeric|min:0|max:100',
            'nilai_pemahaman' => 'required|numeric|min:0|max:100',
            'catatan' => 'nullable',
            'durasi_penilaian' => 'required|numeric|min:0',
        ], [
            'tes_santri_id.required' => 'ID tes santri wajib diisi.',
            'tes_santri_id.exists' => 'ID tes santri tidak ditemukan dalam database.',
            'nilai_makna.required' => 'Nilai makna wajib diisi.',
            'nilai_makna.numeric' => 'Nilai makna harus berupa angka.',
            'nilai_makna.min' => 'Nilai makna minimal adalah 0.',
            'nilai_makna.max' => 'Nilai makna maksimal adalah 100.',
            'nilai_keterangan.required' => 'Nilai keterangan wajib diisi.',
            'nilai_keterangan.numeric' => 'Nilai keterangan harus berupa angka.',
            'nilai_keterangan.min' => 'Nilai keterangan minimal adalah 0.',
            'nilai_keterangan.max' => 'Nilai keterangan maksimal adalah 100.',
            'nilai_penjelasan.required' => 'Nilai penjelasan wajib diisi.',
            'nilai_penjelasan.numeric' => 'Nilai penjelasan harus berupa angka.',
            'nilai_penjelasan.min' => 'Nilai penjelasan minimal adalah 0.',
            'nilai_penjelasan.max' => 'Nilai penjelasan maksimal adalah 100.',
            'nilai_pemahaman.required' => 'Nilai pemahaman wajib diisi.',
            'nilai_pemahaman.numeric' => 'Nilai pemahaman harus berupa angka.',
            'nilai_pemahaman.min' => 'Nilai pemahaman minimal adalah 0.',
            'nilai_pemahaman.max' => 'Nilai pemahaman maksimal adalah 100.',
            'durasi_penilaian.required' => 'Durasi penilaian wajib diisi.',
            'durasi_penilaian.numeric' => 'Durasi penilaian harus berupa angka.',
            'durasi_penilaian.min' => 'Durasi penilaian tidak boleh kurang dari 0.',
        ]);

        $validated['guru_id'] = Auth::id();

        $akademikKediri = AkademikKediri::updateOrCreate(
            [
                'tes_santri_id' => $validated['tes_santri_id'],
                'guru_id' => $validated['guru_id'],
            ],
            $validated
        );

        return response()->json(["message" => "Nilai akademik berhasil disimpan.", "data" => $akademikKediri], 200);
    }

    /**
     * List AkademikKediri entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkademikKediri::class)
            ->allowedFilters(['tes_santri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akademik berhasil diambil.", "data" => $data], 200);
    }
}
