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
            'tes_santri_id' => 'required|exists:tes_santri,id',
            'poin' => 'required|numeric|min:0|max:100',
            'catatan' => 'string|max:255',
        ]);

        $validated['guru_id'] = Auth::id();

        $akhlakKediri = AkhlakKediri::create(
            $validated
        );

        return response()->json(["message" => "Nilai akhlak berhasil disimpan!", "data" => $akhlakKediri], 200);
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
