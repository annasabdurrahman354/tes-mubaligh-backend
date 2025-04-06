<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provinsi;
use App\Models\Kota;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Daerah;
use App\Models\Ponpes;


class OptionsController extends Controller
{
    public function getProvinsi()
    {
        // Adjust 'id_provinsi' and 'n_provinsi' based on your actual column names
        $provinsi = Provinsi::select('id_provinsi', 'nama')->orderBy('nama')->get();
        return response()->json($provinsi);
    }

    public function getKota($provinsi_id)
    {
        // Adjust 'id_kota_kab', 'n_kota_kab', 'provinsi_id' based on your actual column names
        $kota = Kota::where('provinsi_id', $provinsi_id)
            ->select('id_kota_kab', 'nama')
            ->orderBy('nama')
            ->get();
        return response()->json($kota);
    }

    public function getKecamatan($kota_id)
    {
        // Adjust 'id_kecamatan', 'n_kecamatan', 'kota_kab_id' based on your actual column names
        $kecamatan = Kecamatan::where('kota_kab_id', $kota_id)
            ->select('id_kecamatan', 'nama')
            ->orderBy('nama')
            ->get();
        return response()->json($kecamatan);
    }

    public function getKelurahan($kecamatan_id)
    {
        // Adjust 'id_desa_kel', 'n_desa_kel', 'kecamatan_id' based on your actual column names
        $kelurahan = Kelurahan::where('kecamatan_id', $kecamatan_id)
            ->select('id_desa_kel', 'nama')
            ->orderBy('nama')
            ->get();
        return response()->json($kelurahan);
    }

    public function getDaerahSambung()
    {
        // Adjust 'id_daerah', 'n_daerah' based on your actual column names
        $daerah = Daerah::select('id_daerah', 'n_daerah')->orderBy('n_daerah')->get();
        return response()->json($daerah);
    }

    public function getPonpes()
    {
        // Adjust 'id_ponpes', 'n_ponpes' based on your actual column names
        // You might want to add filtering/ordering as needed
        $ponpes = Ponpes::orderBy('n_ponpes')->get()->pluck('id_ponpes', 'namaWithDaerah');
        return response()->json($ponpes);
    }
}
