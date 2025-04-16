<?php

namespace App\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersNamaOrCocard implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $mainTable = $query->getModel()->getTable(); // Get the table name dynamically
        if (preg_match('/\d/', $value)) { // Check if value contains any numbers
            $query->whereRaw("CONCAT(COALESCE({$mainTable}.kelompok, ''), COALESCE({$mainTable}.nomor_cocard, '')) = ?", [$value]);
        } else {
            // Otherwise, perform a 'like' search on the 'nama_lengkap' column
            $query->whereHas('siswa', function (Builder $query) use ($value) {
                $query->where('nama_lengkap', 'like', "%$value%");
            });
        }
    }
}
