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
            'peserta_kediri_id' => 'required|exists:peserta_kediri,id',
            'nilai_makna' => 'nullable|numeric|min:0|max:100',
            'nilai_keterangan' => 'nullable|numeric|min:0|max:100',
            'nilai_penjelasan' => 'nullable|numeric|min:0|max:100',
            'nilai_pemahaman' => 'nullable|numeric|min:0|max:100',
            'catatan' => 'nullable|string|max:255',
        ]);

        $validated['guru_id'] = Auth::id();

        $akademikKediri = AkademikKediri::updateOrCreate(
            [
                'peserta_kediri_id' => $validated['peserta_kediri_id'],
                'guru_id' => $validated['guru_id'],
            ],
            $validated
        );

        return response()->json(["message" => "Nilai akademik berhasil disimpan!", "data" => $akademikKediri], 200);
    }

    /**
     * List AkademikKediri entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkademikKediri::class)
            ->allowedFilters(['peserta_kediri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akademik berhasil diambil!", "data" => $data], 200);
    }
}
