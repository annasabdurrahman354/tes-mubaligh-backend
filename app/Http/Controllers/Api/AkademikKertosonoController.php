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
            'peserta_kertosono_id' => 'required|exists:peserta_kertosono,id',
            'penilaian' => 'required',
            'kekurangan_tajwid' => 'nullable',
            'kekurangan_khusus' => 'nullable',
            'kekurangan_keserasian' => 'nullable',
            'kekurangan_kelancaran' => 'nullable',
            'catatan' => 'nullable|string|max:255',
            'rekomendasi_penarikan' => 'nullable|boolean',
            'durasi_penilaian' => 'required|numeric|min:0',
        ]);

        $validated['guru_id'] = Auth::id();

        $akademikKertosono = AkademikKertosono::updateOrCreate(
            [
                'peserta_kertosono_id' => $validated['peserta_kertosono_id'],
                'guru_id' => $validated['guru_id'],
            ],
            $validated
        );

        return response()->json(["message" => "Nilai akademik berhasil disimpan!", "data" => $akademikKertosono], 200);
    }

    /**
     * List AkademikKediri entries with optional filters using Spatie Query Builder.
     */
    public function index(Request $request)
    {
        $data = QueryBuilder::for(AkademikKertosono::class)
            ->allowedFilters(['peserta_kediri_id', 'guru_id'])
            ->get();

        return response()->json(["message" => "Data nilai akademik berhasil diambil!", "data" => $data], 200);
    }
}
