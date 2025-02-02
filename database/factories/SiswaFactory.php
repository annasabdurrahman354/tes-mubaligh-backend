<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\Provinsi;
use App\Models\Kota;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Daerah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SiswaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Siswa::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nik' => $this->faker->unique()->numerify('############'),
            'nispn' => $this->faker->numerify('###########'),
            'nis' => $this->faker->numerify('########'),
            'nisn' => $this->faker->numerify('########'),
            'kk' => $this->faker->numerify('########'),
            'rfid' => $this->faker->uuid,
            'nama_lengkap' => $this->faker->name,
            'nama_panggilan' => $this->faker->firstName,
            'tempat_lahir' => $this->faker->city,
            'tanggal_lahir' => $this->faker->date('Y-m-d'),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'alamat' => $this->faker->address,
            'rt' => $this->faker->numerify('##'),
            'rw' => $this->faker->numerify('##'),
            'provinsi_id' => Provinsi::inRandomOrder()->first()?->id_provinsi,
            'kota_kab_id' => Kota::inRandomOrder()->first()?->id_kota_kab,
            'kecamatan_id' => Kecamatan::inRandomOrder()->first()?->id_kecamatan,
            'desa_kel_id' => Kelurahan::inRandomOrder()->first()?->id_desa_kel,
            'kode_pos' => $this->faker->postcode,
            'hp' => $this->faker->phoneNumber,
            'email' => $this->faker->safeEmail,
            'pendidikan' => $this->faker->randomElement(['SD', 'SMP', 'SMA', 'D2', 'S1']),
            'jurusan' => $this->faker->word,
            'id_daerah_sambung' => Daerah::inRandomOrder()->first()?->id_daerah,
            'desa_sambung' => $this->faker->city,
            'kelompok_sambung' => $this->faker->word,
            'anak_ke' => $this->faker->numberBetween(1, 10),
            'dari_saudara' => $this->faker->numberBetween(1, 10),
            'status_nikah' => $this->faker->randomElement(['Belum Menikah', 'Menikah']),
            'riwayat_sakit' => $this->faker->sentence,
            'alergi' => $this->faker->word,
            'status_mondok' => $this->faker->randomElement(['person', 'kiriman', 'pelajar']),
            'id_daerah_kiriman' => Daerah::inRandomOrder()->first()?->id_daerah,
            'dapukan' => $this->faker->word,
            'bahasa_makna' => $this->faker->languageCode,
            'bahasa_harian' => $this->faker->languageCode,
            'khatam_hb' => $this->faker->boolean,
            'keahlian' => $this->faker->word,
            'hobi' => $this->faker->word,
            'tinggi_badan' => $this->faker->numberBetween(140, 200),
            'berat_badan' => $this->faker->numberBetween(40, 100),
            'gol_darah' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'sim' => $this->faker->boolean ? 'A' : 'C',
            'img_person' => null,
            'img_identitas' => null,
            'del_status' => false,
            'nama_ayah' => $this->faker->name('male'),
            'status_hidup_ayah' => $this->faker->randomElement(['Hidup', 'Meninggal']),
            'hp_ayah' => $this->faker->phoneNumber,
            'tempat_lahir_ayah' => $this->faker->city,
            'tanggal_lahir_ayah' => $this->faker->date('Y-m-d'),
            'alamat_domisili_ayah' => $this->faker->address,
            'alamat_sambung_ayah' => $this->faker->address,
            'pekerjaan_ayah' => $this->faker->jobTitle,
            'nama_ibu' => $this->faker->name('female'),
            'status_hidup_ibu' => $this->faker->randomElement(['Hidup', 'Meninggal']),
            'hp_ibu' => $this->faker->phoneNumber,
            'tempat_lahir_ibu' => $this->faker->city,
            'tanggal_lahir_ibu' => $this->faker->date('Y-m-d'),
            'alamat_domisili_ibu' => $this->faker->address,
            'alamat_sambung_ibu' => $this->faker->address,
            'pekerjaan_ibu' => $this->faker->jobTitle,
        ];
    }
}
