<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\StatusMondok;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'tb_personal_data3';
    protected $primaryKey = 'nik';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nik', 'nispn', 'nis', 'nisn', 'kk', 'rfid', 'nama_lengkap', 'nama_panggilan',
        'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'rt', 'rw',
        'provinsi_id', 'kota_kab_id', 'kecamatan_id', 'desa_kel_id',
        'kode_pos', 'hp', 'email', 'pendidikan', 'jurusan',
        'id_daerah_sambung', 'desa_sambung', 'kelompok_sambung',
        'anak_ke', 'dari_saudara', 'status_nikah', 'riwayat_sakit', 'alergi',
        'status_mondok', 'id_daerah_kiriman', 'dapukan',
        'bahasa_makna', 'bahasa_harian', 'khatam_hb', 'keahlian', 'hobi',
        'tinggi_badan', 'berat_badan', 'gol_darah', 'sim', 'img_person',
        'img_identitas', 'del_status', 'nama_ayah', 'status_hidup_ayah',
        'hp_ayah', 'tempat_lahir_ayah', 'tanggal_lahir_ayah',
        'alamat_domisili_ayah', 'alamat_sambung_ayah', 'pekerjaan_ayah',
        'nama_ibu', 'status_hidup_ibu', 'hp_ibu', 'tempat_lahir_ibu',
        'tanggal_lahir_ibu', 'alamat_domisili_ibu', 'alamat_sambung_ibu',
        'pekerjaan_ibu'
    ];

    protected $casts =[
        'tanggal_lahir' => 'date:d-m-Y',
        'tanggal_lahir_ayah' => 'date:d-m-Y',
        'tanggal_lahir_ibu' => 'date:d-m-Y',
        'jenis_kelamin' => JenisKelamin::class,
        'status_mondok' => StatusMondok::class,
    ];

    protected function umur(): Attribute
    {
        // Calculate age using Carbon
        return Attribute::make(
            get: fn () => Carbon::parse($this->tanggal_lahir)->age,
        );
    }

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id_provinsi');
    }

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kota_kab_id', 'id_kota_kab');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id_kecamatan');
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'desa_kel_id', 'id_desa_kel');
    }

    public function daerahSambung(): BelongsTo
    {
        return $this->belongsTo(Daerah::class, 'id_daerah_sambung', 'id_daerah');
    }

    public function daerahKiriman(): BelongsTo
    {
        return $this->belongsTo(Daerah::class, 'id_daerah_kiriman', 'id_daerah');
    }

    public function pesertaKediri()
    {
        return $this->hasMany(PesertaKediri::class, 'nispn', 'nispn');
    }

    public function pesertaKertosono()
    {
        return $this->hasMany(PesertaKertosono::class, 'nispn', 'nispn');
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Section::make('Nomor Identitas')
                ->schema([
                    Forms\Components\TextInput::make('nik')
                        ->label('Nomor Induk Kependudukan')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->length(16),
                    Forms\Components\TextInput::make('nispn')
                        ->label('Nomor Induk Santri Pondok Nasional')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->length(19),
                    Forms\Components\TextInput::make('nis')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('nisn')
                        ->length(10),
                    Forms\Components\TextInput::make('kk')
                        ->label('Nomor Kartu Keluarga')
                        ->length(16),
                    Forms\Components\TextInput::make('rfid')
                        ->label('Kode RFID')
                        ->maxLength(10),
                ]),

            Forms\Components\Section::make('Data Pribadi')
                ->schema([
                    Forms\Components\TextInput::make('nama_lengkap')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nama_panggilan')
                        ->label('Nama Panggilan')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('tempat_lahir')
                        ->label('Tempat Lahir')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\DatePicker::make('tanggal_lahir')
                        ->label('Tanggal Lahir')
                        ->required(),
                    Forms\Components\Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan'
                        ])
                        ->required(),
                    Forms\Components\Select::make('status_nikah')
                        ->label('Status Pernikahan')
                        ->options([
                            'Belum Menikah' => 'Belum Menikah',
                            'Menikah' => 'Menikah',
                            'Cerai' => 'Cerai'
                        ]),
                ]),

            Forms\Components\Section::make('Alamat dan Kontak')
                ->schema([
                    Forms\Components\Textarea::make('alamat')
                        ->label('Alamat')
                        ->maxLength(150)
                        ->required(),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('rt')
                                ->label('RT')
                                ->numeric()
                                ->maxLength(3),
                            Forms\Components\TextInput::make('rw')
                                ->label('RW')
                                ->numeric()
                                ->maxLength(3),
                        ]),
                    Forms\Components\TextInput::make('provinsi_id')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('kota_kab_id')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('kecamatan_id')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('desa_kel_id')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('kode_pos')
                        ->label('Kode Pos')
                        ->maxLength(10),
                    Forms\Components\TextInput::make('hp')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(15),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Pendidikan')
                ->schema([
                    Forms\Components\Select::make('pendidikan')
                        ->label('Pendidikan')
                        ->options([
                            'SD' => 'SD',
                            'SMP' => 'SMP',
                            'SMA' => 'SMA',
                            'SMK' => 'SMK',
                            'Paket C' => 'Paket C',
                            'D3' => 'D3',
                            'S1' => 'S1'
                        ]),
                    Forms\Components\TextInput::make('jurusan')
                        ->label('Jurusan')
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Data Daerah')
                ->schema([
                    Forms\Components\TextInput::make('id_daerah_sambung')
                        ->label('Daerah Sambung')
                        ->maxLength(11),
                    Forms\Components\TextInput::make('desa_sambung')
                        ->label('Desa Sambung')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('kelompok_sambung')
                        ->label('Kelompok Sambung')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('id_daerah_kiriman')
                        ->label('Daerah Kiriman')
                        ->maxLength(11),
                ]),

            Forms\Components\Section::make('Data Keluarga')
                ->schema([
                    Forms\Components\TextInput::make('anak_ke')
                        ->label('Anake Ke')
                        ->maxLength(2)
                        ->numeric(),
                    Forms\Components\TextInput::make('dari_saudara')
                        ->label('Jumlah Saudara')
                        ->maxLength(2)
                        ->numeric(),
                ]),

            Forms\Components\Section::make('Kesehatan dan Fisik')
                ->schema([
                    Forms\Components\Textarea::make('riwayat_sakit')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('alergi')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('tinggi_badan')
                        ->maxLength(3)
                        ->numeric(),
                    Forms\Components\TextInput::make('berat_badan')
                        ->maxLength(3)
                        ->numeric(),
                    Forms\Components\Select::make('gol_darah')
                        ->options([
                            'A' => 'A',
                            'B' => 'B',
                            'AB' => 'AB',
                            'O' => 'O',
                            '-' => 'Tidak Tahu'
                        ]),
                ]),

            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([
                    Forms\Components\TextInput::make('status_mondok')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('dapukan')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('bahasa_makna')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('bahasa_harian')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('khatam_hb')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('keahlian')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('hobi')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('sim')
                        ->maxLength(20),
                ]),

            Forms\Components\Section::make('Dokumen dan Gambar')
                ->schema([
                    Forms\Components\FileUpload::make('img_person')
                        ->image()
                        ->directory('personal-images'),
                    Forms\Components\FileUpload::make('img_identitas')
                        ->image()
                        ->directory('identity-images'),
                ]),

            Forms\Components\Section::make('Data Ayah')
                ->schema([
                    Forms\Components\TextInput::make('nama_ayah')
                        ->maxLength(255),
                    Forms\Components\Select::make('status_hidup_ayah')
                        ->options([
                            'Hidup' => 'Hidup',
                            'Wafat' => 'Wafat'
                        ]),
                    Forms\Components\TextInput::make('hp_ayah')
                        ->tel()
                        ->maxLength(15),
                    Forms\Components\TextInput::make('tempat_lahir_ayah')
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('tanggal_lahir_ayah'),
                    Forms\Components\Textarea::make('alamat_domisili_ayah')
                        ->maxLength(150),
                    Forms\Components\TextInput::make('alamat_sambung_ayah')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('pekerjaan_ayah')
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Data Ibu')
                ->schema([
                    Forms\Components\TextInput::make('nama_ibu')
                        ->label('Nama Ibu')
                        ->maxLength(255),
                    Forms\Components\Select::make('status_hidup_ibu')
                        ->label('Status Ibu')
                        ->options([
                            'Hidup' => 'Hidup',
                            'Wafat' => 'Wafat'
                        ]),
                    Forms\Components\TextInput::make('hp_ibu')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(15),
                    Forms\Components\TextInput::make('tempat_lahir_ibu')
                        ->label('Tempat Lahir')
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('tanggal_lahir_ibu')
                        ->label('Tanggal Lahir'),
                    Forms\Components\Textarea::make('alamat_domisili_ibu')
                        ->maxLength(150),
                    Forms\Components\TextInput::make('alamat_sambung_ibu')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('pekerjaan_ibu')
                        ->maxLength(50),
                ]),
        ];
    }

    public static function getColumns(): array
    {
        return [
            // Identity Numbers
            Tables\Columns\TextColumn::make('nik')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nispn')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nis')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nisn')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kk')
                ->label('No. KK')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('rfid')
                ->searchable()
                ->toggleable(),

            // Personal Information
            Tables\Columns\TextColumn::make('nama_lengkap')
                ->label('Nama Lengkap')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nama_panggilan')
                ->label('Nama Panggilan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tempat_lahir')
                ->label('Tempat Lahir')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tanggal_lahir')
                ->label('Tanggal Lahir')
                ->date()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('umur')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->sortable()
                ->toggleable(),

            // Address
            Tables\Columns\TextColumn::make('alamat')
                ->wrap()
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('rt')
                ->toggleable(),
            Tables\Columns\TextColumn::make('rw')
                ->toggleable(),
            Tables\Columns\TextColumn::make('provinsi')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('provinsi_id')
                ->toggleable(),
            Tables\Columns\TextColumn::make('kota_kab')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kota_kab_id')
                ->toggleable(),
            Tables\Columns\TextColumn::make('kecamatan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kecamatan_id')
                ->toggleable(),
            Tables\Columns\TextColumn::make('desa_kel')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('desa_kel_id')
                ->toggleable(),
            Tables\Columns\TextColumn::make('kode_pos')
                ->toggleable(),

            // Contact
            Tables\Columns\TextColumn::make('hp')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->toggleable(),

            // Education
            Tables\Columns\TextColumn::make('pendidikan')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('jurusan')
                ->searchable()
                ->toggleable(),

            // Region Data
            Tables\Columns\TextColumn::make('id_daerah_sambung')
                ->toggleable(),
            Tables\Columns\TextColumn::make('daerah_sambung')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('desa_sambung')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kelompok_sambung')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('id_daerah_kiriman')
                ->toggleable(),
            Tables\Columns\TextColumn::make('daerah_kiriman')
                ->searchable()
                ->toggleable(),

            // Family Information
            Tables\Columns\TextColumn::make('anak_ke')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('dari_saudara')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('status_nikah')
                ->searchable()
                ->sortable()
                ->toggleable(),

            // Health Information
            Tables\Columns\TextColumn::make('riwayat_sakit')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alergi')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tinggi_badan')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('berat_badan')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('gol_darah')
                ->toggleable(),

            // Additional Information
            Tables\Columns\TextColumn::make('status_mondok')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('dapukan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('bahasa_makna')
                ->toggleable(),
            Tables\Columns\TextColumn::make('bahasa_harian')
                ->toggleable(),
            Tables\Columns\TextColumn::make('khatam_hb')
                ->toggleable(),
            Tables\Columns\TextColumn::make('keahlian')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('hobi')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('sim')
                ->toggleable(),

            // Father's Information
            Tables\Columns\TextColumn::make('nama_ayah')
                ->label('Nama Ayah')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('status_hidup_ayah')
                ->label('Status Ayah')
                ->toggleable(),
            Tables\Columns\TextColumn::make('hp_ayah')
                ->label('No. Telepon Ayah')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tempat_lahir_ayah')
                ->label('Tempat Lahir Ayah')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tanggal_lahir_ayah')
                ->label('Tanggal Lahir Ayah')
                ->date()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alamat_domisili_ayah')
                ->label('Alamat Ayah')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alamat_sambung_ayah')
                ->label('Alamat Sambung Ayah')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('pekerjaan_ayah')
                ->label('Pekerjaan Ayah')
                ->searchable()
                ->toggleable(),

            // Mother's Information
            Tables\Columns\TextColumn::make('nama_ibu')
                ->label('Nama Ibu')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('status_hidup_ibu')
                ->label('Status Ibu')
                ->toggleable(),
            Tables\Columns\TextColumn::make('hp_ibu')
                ->label('No. Telelpon Ibu')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tempat_lahir_ibu')
                ->label('Tempat Lahir Ibu')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tanggal_lahir_ibu')
                ->label('Tanggal Lahir Ibu')
                ->date()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alamat_domisili_ibu')
                ->label('Alamat Ibu')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alamat_sambung_ibu')
                ->label('Alamat Sambung Ibu')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('pekerjaan_ibu')
                ->label('Pekerjaan Ibu')
                ->searchable()
                ->toggleable(),

            // Timestamps
            Tables\Columns\TextColumn::make('created_at_person')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at_person')
                ->label('Updated At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
