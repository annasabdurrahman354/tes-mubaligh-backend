<?php

use App\Models\Ponpes;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Str;

if (!function_exists('generateNispn')) {
    /**
     * Menghasilkan Nomor Induk Santri Pondok Pesantren Nasional (NISPN) berdasarkan ID Ponpes.
     *
     * @param int $id_ponpes ID unik Pondok Pesantren.
     * @return string NISPN yang dihasilkan.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Jika Ponpes tidak ditemukan.
     */
    function generateNispn(int $id_ponpes): string
    {
        // 1. Dapatkan data Ponpes dan ID Daerah terkait
        $ponpes = Ponpes::findOrFail($id_ponpes);
        $id_daerah = $ponpes->id_daerah; // Asumsi id_daerah ada di tabel ponpes

        // 2. Dapatkan komponen waktu saat ini
        $tahun4digit = Carbon::now()->format('Y');
        $bulan = Carbon::now()->format('m');

        // 3. Dapatkan nomor urut (Counter) - Ingat potensi race condition!
        // Transaksi dan unique constraint di DB tetap penting di bagian pemanggil.
        $count = Siswa::count() + 1;

        // 4. Format (Padding) semua komponen numerik
        $idPonpes3digit = str_pad($id_ponpes, 3, '0', STR_PAD_LEFT);
        $idDaerah3digit = str_pad($id_daerah, 3, '0', STR_PAD_LEFT);
        $id_user7 = str_pad($count, 7, '0', STR_PAD_LEFT);

        // 5. Gabungkan menjadi NISPN
        $nispn = "{$tahun4digit}{$bulan}{$idPonpes3digit}{$idDaerah3digit}{$id_user7}";

        return $nispn;
    }
}

if(! function_exists('getPeriodePendaftaran')) {
    function getPeriodePendaftaran()
    {
        return \App\Models\Periode::where('status', \App\Enums\StatusPeriode::REGISTRASI)->orderBy('id_periode', 'DESC')->first()->id_periode;
    }
}


if(! function_exists('getPeriodeTes')) {
    function getPeriodeTes()
    {
        return \App\Models\Periode::where('status', \App\Enums\StatusPeriode::PENGETESAN)->orderBy('id_periode', 'DESC')->first()->id_periode;
    }
}

if(! function_exists('getYearAndMonthName')) {
    function getYearAndMonthName(int $id_periode): array
    {
        // Convert the integer to a string
        $periodeString = (string)$id_periode;

        // Extract year and month number
        $year = substr($periodeString, 0, 4);  // First 4 characters are the year
        $monthNumber = substr($periodeString, 4, 2); // Next 2 characters are the month

        // Get the month name (assuming BulanNomor::from() exists and works as intended)
        $monthName = \App\Enums\BulanNomor::from($monthNumber)->getLabel();

        // Return the result as an associative array
        return [
            'year' => $year,
            'monthName' => $monthName
        ];
    }
}

if(! function_exists('getMediaFilename')) {
    function getMediaFilename($model, $media){
        switch($media){
            case 'guru_foto':
                return 'guru_foto' . '_' . Str::slug($model->nama). '_' . $media->id . '.' . $media->extension;
            default:
                return Str::slug($media->file_name) . '_' . $media->id . '.' . $media->extension;
        }
    }
}

if(!function_exists('formatNamaProper')) {
    function formatNamaProper($nama) {
        // Daftar gelar (case-insensitive)
        $gelar = [
            'H.', 'Hj.', 'Ir.', 'Dr.', 'dr.', 'Prof.', 'S.Pd.', 'M.T.', 'M.Sc.', 'M.Eng.', 'S.T.',
            'S.Kom.', 'S.H.', 'S.Sos.', 'S.E.', 'S.Ked.', 'S.Psi.', 'M.A.', 'M.Kom.', 'M.Si.',
            'Ph.D.', 'Sp.A.', 'Sp.B.', 'Sp.OG.', 'Sp.PD.', 'Sp.KK.', 'Sp.M.', 'Sp.THT.', 'Sp.Rad.',
            'Sp.JP.', 'Sp.An.', 'Sp.Or.', 'Sp.N.', 'KH.', 'Ust.', 'Ustz.', 'Tn.', 'Ny.', 'Nn.',
            'R.', 'Drs.', 'Drg.', 'Dra.', 'Lc.', 'H.C.', 'M.Pd.', 'M.B.A.', 'A.Md.', 'A.Md.Keb.',
            'A.Md.Farm.', 'B.Sc.', 'M.M.', 'S.IP.', 'S.Pt.', 'S.I.Kom.', 'S.Si.', 'S.Ap.', 'S.I.P.',
            'S.Tr.Kes.', 'S.Tr.T.', 'S.Tr.H.', 'S.Tr.P.', 'M.Ed.', 'D.D.S.', 'B.A.', 'M.Th.', 'D.Th.'
        ];

        $parts = explode(' ', $nama);
        $gelarDepan = [];
        $gelarBelakang = [];
        $namaUtama = [];

        // Deteksi gelar di awal
        foreach ($parts as $index => $part) {
            if (in_array(ucfirst(strtolower($part)), array_map('ucfirst', array_map('strtolower', $gelar)))) {
                $gelarDepan[] = $part;
            } else {
                $namaUtama = array_slice($parts, $index);
                break;
            }
        }

        // Deteksi gelar di akhir
        $namaSementara = [];
        foreach (array_reverse($namaUtama) as $index => $part) {
            if (in_array(ucfirst(strtolower($part)), array_map('ucfirst', array_map('strtolower', $gelar)))) {
                $gelarBelakang[] = $part;
            } else {
                $namaSementara = array_reverse(array_slice(array_reverse($namaUtama), $index));
                break;
            }
        }
        $gelarBelakang = array_reverse($gelarBelakang);
        $namaUtama = $namaSementara;

        // Format nama utama
        $namaProper = '';
        if (count($namaUtama) > 0) {
            $namaProper = ucfirst(strtolower($namaUtama[0])); // Kata pertama tetap utuh
            if (isset($namaUtama[1])) {
                $namaProper .= ' ' . ucfirst(strtolower($namaUtama[1])); // Kata kedua tetap utuh
            }
            for ($i = 2; $i < count($namaUtama); $i++) {
                $namaProper .= ' ' . strtoupper(substr($namaUtama[$i], 0, 1)) . '.'; // Sisanya disingkat
            }
        }

        // Gabungkan gelar dan nama
        return implode(' ', array_merge($gelarDepan, [$namaProper], $gelarBelakang));
    }
}

if (!function_exists('excelProper')) {
    /**
     * Meniru fungsi PROPER() di Excel.
     * Mengkapitalisasi huruf pertama dan huruf setelah karakter non-huruf,
     * serta mengubah huruf lainnya menjadi huruf kecil.
     * Fungsi ini mendukung karakter multi-byte (UTF-8).
     * Jika input null, mengembalikan string kosong.
     *
     * @param string|null $string String input yang akan diubah, atau null.
     * @return string String yang sudah diubah ke format Proper Case, atau string kosong jika input null.
     */
    function excelProper(?string $string): string // Changed type hint to nullable: ?string
    {
        // *** START: Added null check ***
        // Jika input adalah null, langsung kembalikan string kosong.
        if ($string === null) {
            return '';
        }
        // *** END: Added null check ***

        // 1. Ubah seluruh string menjadi lowercase terlebih dahulu.
        //    Ini menangani aturan "mengubah semua huruf lainnya menjadi huruf kecil".
        //    Gunakan mb_strtolower untuk mendukung UTF-8 (misal: é, ç, à).
        //    Kode ini hanya akan berjalan jika $string bukan null.
        $string = mb_strtolower($string, 'UTF-8');

        $result = '';
        $len = mb_strlen($string, 'UTF-8');
        $capitalizeNext = true; // Tandai untuk mengkapitalisasi huruf pertama yang ditemukan

        // 2. Iterasi melalui setiap karakter dalam string
        for ($i = 0; $i < $len; $i++) {
            // Ambil karakter saat ini (aman untuk multi-byte)
            $char = mb_substr($string, $i, 1, 'UTF-8');

            // 3. Cek apakah karakter saat ini adalah huruf (menggunakan properti Unicode \p{L})
            //    \p{L} cocok dengan huruf apa pun dalam standar Unicode.
            $isLetter = preg_match('/^\p{L}$/u', $char);

            if ($capitalizeNext && $isLetter) {
                // Jika kita harus mengkapitalisasi dan karakter ini adalah huruf:
                // - Ubah menjadi uppercase (aman untuk multi-byte)
                // - Tambahkan ke hasil
                // - Setel flag agar tidak mengkapitalisasi huruf berikutnya secara langsung
                $result .= mb_strtoupper($char, 'UTF-8');
                $capitalizeNext = false;
            } else {
                // Jika tidak perlu mengkapitalisasi, atau jika karakter ini bukan huruf:
                // - Tambahkan karakter apa adanya ke hasil (sudah lowercase atau bukan huruf)
                $result .= $char;

                // 4. Cek apakah karakter saat ini BUKAN huruf.
                //    Jika ya, maka huruf BERIKUTNYA (jika ada) harus dikapitalisasi.
                if (!$isLetter) {
                    $capitalizeNext = true;
                }
            }
        }

        return $result;
    }
}

if(!function_exists('formattedDateTime')) {
    function formattedDateTime(string $date): string
    {
        return Carbon::parse($date)->translatedFormat('d F Y') . ' pukul ' .Carbon::parse($date)->translatedFormat('H.i');
    }
}

if(!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return auth()->user()->hasRole(\App\Enums\Role::SUPER_ADMIN);
    }
}

if(!function_exists('isAdminKediri')) {
    function isAdminKediri() {
        return auth()->user()->hasRole(\App\Enums\Role::ADMIN_KEDIRI);
    }
}

if(!function_exists('isAdminKertosono')) {
    function isAdminKertosono() {
        return auth()->user()->hasRole(\App\Enums\Role::ADMIN_KERTOSONO);
    }
}

if(!function_exists('isOperatorPondok')) {
    function isOperatorPondok() {
        return auth()->user()->hasRole(\App\Enums\Role::OPERATOR_PONDOK);
    }
}

if(!function_exists('isGuruKediri')) {
    function isGuruKediri() {
        return auth()->user()->hasRole(\App\Enums\Role::GURU_KEDIRI);
    }
}

if(!function_exists('isGuruKertosono')) {
    function isGuruKertosono() {
        return auth()->user()->hasRole(\App\Enums\Role::GURU_KERTOSONO);
    }
}

if(!function_exists('isPembina')) {
    function isPembina() {
        return auth()->user()->hasRole(\App\Enums\Role::PEMBINA);
    }
}

if(!function_exists('isKomandan')) {
    function isKomandan() {
        return auth()->user()->hasRole(\App\Enums\Role::KOMANDAN);
    }
}

if(!function_exists('cant')) {
    function cant($abilities) {
        return !auth()->user()->can($abilities);
    }
}

if(!function_exists('can')) {
    function can($abilities) {
        if (isSuperAdmin()){
            return true;
        }
        else return auth()->user()->can($abilities);
    }
}

if(!function_exists('getProgramStudiList')) {
    function getProgramStudiList(){
        return [
            "Administrasi Publik",
            "Agribisnis",
            "Agronomi",
            "Agroteknologi",
            "Akuntansi",
            "Arsitektur",
            "Bahasa Inggris",
            "Bahasa Mandarin",
            "Bahasa Mandarin dan Kebudayaan Tiongkok",
            "Bimbingan dan Konseling",
            "Biologi",
            "Biosain",
            "Bisnis Digital",
            "Budi Daya Ternak",
            "Demografi dan Pencatatan Sipil",
            "Desain Interior",
            "Desain Komunikasi Visual",
            "Ekonomi Pembangunan",
            "Ekonomi dan Studi Pembangunan",
            "Farmasi",
            "Fisika",
            "Hubungan Internasional",
            "Ilmu Administrasi Negara",
            "Ilmu Ekonomi",
            "Ilmu Fisika",
            "Ilmu Gizi",
            "Ilmu Hukum",
            "Ilmu Kedokteran",
            "Ilmu Keolahragaan",
            "Ilmu Kesehatan Masyarakat",
            "Ilmu Komunikasi",
            "Ilmu Lingkungan",
            "Ilmu Linguistik",
            "Ilmu Pendidikan",
            "Ilmu Pertanian",
            "Ilmu Sejarah",
            "Ilmu Tanah",
            "Ilmu Teknik Mesin",
            "Ilmu Teknik Sipil",
            "Ilmu Teknologi Pangan",
            "Informatika",
            "Kajian Budaya",
            "Kebidanan",
            "Kebidanan Terapan",
            "Kedokteran",
            "Kenotariatan",
            "Keperawatan Anestesiologi",
            "Keselamatan dan Kesehatan Kerja",
            "Keuangan dan Perbankan",
            "Kimia",
            "Komunikasi Terapan",
            "Kriya Seni",
            "Linguistik",
            "Manajemen",
            "Manajemen Administrasi",
            "Manajemen Bisnis",
            "Manajemen Pemasaran",
            "Manajemen Perdagangan",
            "Matematika",
            "Pendidikan Administrasi Perkantoran",
            "Pendidikan Akuntansi",
            "Pendidikan Bahasa dan Sastra Indonesia",
            "Pendidikan Bahasa Indonesia",
            "Pendidikan Bahasa Inggris",
            "Pendidikan Bahasa Jawa",
            "Pendidikan Bahasa dan Sastra Daerah",
            "Pendidikan Biologi",
            "Pendidikan Ekonomi",
            "Pendidikan Fisika",
            "Pendidikan Geografi",
            "Pendidikan Guru Pendidikan Anak Usia Dini",
            "Pendidikan Guru Sekolah Dasar",
            "Pendidikan Guru Sekolah Dasar (Kampus Kabupaten Kebumen)",
            "Pendidikan Guru Vokasi",
            "Pendidikan Ilmu Pengetahuan Alam",
            "Pendidikan Jasmani, Kesehatan dan Rekreasi",
            "Pendidikan Kepelatihan Olahraga",
            "Pendidikan Kimia",
            "Pendidikan Luar Biasa",
            "Pendidikan Matematika",
            "Pendidikan Pancasila dan Kewarganegaraan",
            "Pendidikan Pancasila dan Kewarganegaraan",
            "Pendidikan Profesi Bidan",
            "Pendidikan Profesi Guru",
            "Pendidikan Profesi Guru SD",
            "Pendidikan Sains",
            "Pendidikan Sejarah",
            "Pendidikan Seni",
            "Pendidikan Seni Rupa",
            "Pendidikan Sosiologi Antropologi",
            "Pendidikan Teknik Bangunan",
            "Pendidikan Teknik Informatika & Komputer",
            "Pendidikan Teknik Mesin",
            "Pengelolaan Hutan",
            "Penyuluhan Pembangunan",
            "Penyuluhan Pembangunan/Pemberdayaan Masyarakat",
            "Penyuluhan dan Komunikasi Pertanian",
            "Perencanaan Wilayah dan Kota",
            "Perpajakan",
            "Perpustakaan",
            "Peternakan",
            "Profesi Apoteker",
            "Profesi Dokter",
            "Program Profesi Insinyur",
            "Psikologi",
            "Sains Data",
            "Sastra Arab",
            "Sastra Daerah",
            "Sastra Indonesia",
            "Sastra Inggris",
            "Seni Rupa",
            "Seni Rupa Murni",
            "Sosiologi",
            "Statistika",
            "Teknik Elektro",
            "Teknik Industri",
            "Teknik Informatika",
            "Teknik Kimia",
            "Teknik Mesin",
            "Teknik Sipil",
            "Teknologi Hasil Pertanian",
            "Teknologi Pendidikan",
            "Usaha Perjalanan Wisata"
        ];
    }
}
