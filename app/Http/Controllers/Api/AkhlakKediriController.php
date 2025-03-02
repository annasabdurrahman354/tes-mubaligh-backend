<?php

namespace App\Http\Controllers\Api;

use App\Models\AkhlakKediri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class AkhlakKediriController extends Controller
{
    /**
     * Create or update an AkhlakKediri entry by peserta_id and guru_id.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tes_santri_id' => 'required|exists:tb_tes_santri,id_tes_santri',
            'poin' => 'required|numeric|min:0|max:100',
            'catatan' => 'nullable|string|max:255',
        ], [
            'tes_santri_id.required' => 'ID tes santri wajib diisi.',
            'tes_santri_id.exists' => 'ID tes santri tidak ditemukan dalam database.',
            'poin.required' => 'Poin akhlak wajib diisi.',
            'poin.numeric' => 'Poin akhlak harus berupa angka.',
            'poin.min' => 'Poin akhlak minimal adalah 0.',
            'poin.max' => 'Poin akhlak maksimal adalah 100.',
            'catatan.string' => 'Catatan harus berupa teks.',
            'catatan.max' => 'Catatan tidak boleh lebih dari 255 karakter.',
        ]);

        $validated['guru_id'] = Auth::id();

        $akhlakKediri = AkhlakKediri::create($validated);

        return response()->json(["message" => "Nilai akhlak berhasil disimpan.", "data" => $akhlakKediri], 200);
    }

    /**
     * List AkhlakKediri entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkhlakKediri::class)
            ->allowedFilters(['tes_santri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akhlak berhasil diambil.", "data" => $data], 200);
    }
}
