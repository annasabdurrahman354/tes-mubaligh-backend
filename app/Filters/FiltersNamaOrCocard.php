<?php

namespace App\Filters;

use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersNamaOrCocard implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (preg_match('/\d/', $value)) { // Check if value contains any numbers
            $query->selectRaw("*, CONCAT(COALESCE(tes_santri.kelompok, ''), COALESCE(tes_santri.nomor_cocard, '')) as kelompok_cocard")
                ->having('kelompok_cocard', '=', $value);
        } else {
            // Otherwise, perform a 'like' search on the 'nama_lengkap' column
            $query->whereHas('siswa', function (Builder $query) use ($value) {
                $query->where('nama_lengkap', 'like', "%$value%");
            });
        }
    }
}
