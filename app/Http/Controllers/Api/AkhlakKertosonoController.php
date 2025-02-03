<?php

namespace App\Http\Controllers\Api;

use App\Models\AkhlakKediri;
use App\Models\AkhlakKertosono;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class AkhlakKertosonoController extends Controller
{
    /**
     * Create or update an AkhlakKertosono entry by peserta_id and guru_id.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tes_santri_id' => 'required|exists:tes_santri_id,id',
            'catatan' => 'nullable|string|max:255',
        ]);

        $validated['guru_id'] = Auth::id();

        $akhlakKertosono = AkhlakKertosono::create(
            $validated
        );

        return response()->json(["message" => "Nilai akhlak berhasil disimpan!", "data" => $akhlakKertosono], 200);
    }

    /**
     * List AkhlakKertosono entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkhlakKertosono::class)
            ->allowedFilters(['tes_santri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akhlak berhasil diambil.", "data" => $data], 200);
    }
}
