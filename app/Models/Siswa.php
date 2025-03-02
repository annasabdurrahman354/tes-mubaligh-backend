<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\StatusMondok;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Get;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Siswa extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'tb_personal_data';
    protected $primaryKey = 'nik';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

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

    protected function urlFotoIdentitas(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->img_identitas ? 'https://ppwb.kita-kita.online/sisfo-ponpes/images/'.$this->img_identitas : $this->getFirstMediaUrl('siswa_foto_identitas', 'thumb'),
        );
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 256, 256)
            ->optimize()
            ->nonQueued();
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Section::make('Nomor Identitas')
                ->schema([
                    Forms\Components\TextInput::make('nik')
                        ->label('Nomor Induk Kependudukan')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('kk')
                        ->label('Nomor Kartu Keluarga')
                        ->length(16),
                    Forms\Components\TextInput::make('nispn')
                        ->label('Nomor Induk Santri Pondok Nasional')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('nis')
                        ->label('Nomor Induk Santri')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('nisn')
                        ->label('Nomor Induk Siswa Nasional')
                        ->length(10),
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
                        ->options(JenisKelamin::class)
                        ->required(),
                    Forms\Components\Select::make('status_nikah')
                        ->label('Status Pernikahan')
                        ->options([
                            'Belum Menikah' => 'Belum Menikah',
                            'Menikah' => 'Menikah',
                        ]),
                ]),

            Forms\Components\Section::make('Dokumen dan Gambar')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('foto')
                        ->label('Foto')
                        ->avatar()
                        ->collection('siswa_foto_identitas')
                        ->conversion('thumb')
                        ->moveFiles()
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('img_person')
                        ->label('Nama Image Person'),
                    Forms\Components\TextInput::make('img_identitas')
                        ->label('Nama Image Identitas'),
                ]),

            Forms\Components\Section::make('Alamat dan Kontak')
                ->schema([
                    Forms\Components\Textarea::make('alamat')
                        ->label('Alamat')
                        ->maxLength(150),
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
                    Select::make('provinsi_id')
                        ->label('Provinsi')
                        ->relationship('provinsi', 'nama')
                        ->searchable()
                        ->live(),
                    Select::make('kota_kab_id')
                        ->label('Kota')
                        ->relationship(
                            name: 'kota',
                            titleAttribute: 'nama',
                            modifyQueryUsing: fn (Builder $query, Get $get) =>
                            $query->where('provinsi_id', $get('provinsi_id')),
                        )
                        ->searchable()
                        ->visible(fn (Get $get) => $get('provinsi_id'))
                        ->live(),
                    Select::make('kecamatan_id')
                        ->label('Kecamatan')
                        ->relationship(
                            name: 'kecamatan',
                            titleAttribute: 'nama',
                            modifyQueryUsing: fn (Builder $query, Get $get) =>
                            $query->where('kota_kab_id', $get('kota_kab_id')),
                        )
                        ->searchable()
                        ->visible(fn (Get $get) => $get('kota_kab_id'))
                        ->live(),
                    Select::make('desa_kel_id')
                        ->label('Kelurahan')
                        ->relationship(
                            name: 'kelurahan',
                            titleAttribute: 'nama',
                            modifyQueryUsing: fn (Builder $query, Get $get) =>
                            $query->where('kecamatan_id', $get('kecamatan_id')),
                        )
                        ->searchable()
                        ->visible(fn (Get $get) => $get('kecamatan_id')),

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

            Forms\Components\Section::make('Alamat Sambung')
                ->schema([
                    Forms\Components\Select::make('id_daerah_sambung')
                        ->label('Daerah Sambung')
                        ->relationship('daerahSambung', 'n_daerah')
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('desa_sambung')
                        ->label('Desa Sambung')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('kelompok_sambung')
                        ->label('Kelompok Sambung')
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Data Mondok')
                ->schema([
                    Forms\Components\Select::make('status_mondok')
                        ->label('Status Mondok')
                        ->options(StatusMondok::class),
                    Forms\Components\Select::make('id_daerah_kiriman')
                        ->label('Daerah Kiriman')
                        ->relationship('daerahKiriman', 'n_daerah')
                        ->searchable()
                        ->requiredIf('status_mondok', 'kiriman'),
                    Forms\Components\TextInput::make('dapukan')
                        ->label('Dapukan')
                        ->maxLength(100),
                    Forms\Components\Select::make('bahasa_makna')
                        ->label('Bahasa Makna')
                        ->options([
                            'Jawa' => 'Jawa',
                            'Indonesia' => 'Indonesia',
                            'Indonesia Jawa' => 'Indonesia Jawa',
                        ]),
                    Forms\Components\TextInput::make('bahasa_harian')
                        ->label('Bahasa Harian')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('khatam_hb')
                        ->label('Khatam HB')
                        ->maxLength(255),
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
                            'S1' => 'S1',
                            'S2' => 'S2',
                            'S3' => 'S3'
                        ]),
                    Forms\Components\TextInput::make('jurusan')
                        ->label('Jurusan')
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([
                    Forms\Components\TextInput::make('keahlian')
                        ->label('Keahlian')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('hobi')
                        ->label('Hobi')
                        ->maxLength(100),
                    Forms\Components\Select::make('sim')
                        ->label('SIM')
                        ->options([
                            'A' => 'A',
                            'B' => 'B',
                            'C' => 'C',
                        ])
                        ->multiple(),
                    Forms\Components\Textarea::make('riwayat_sakit')
                        ->label('Riwayat Sakit')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('alergi')
                        ->label('Alergi')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('tinggi_badan')
                        ->label('Tinggi Badan')
                        ->maxLength(3)
                        ->numeric(),
                    Forms\Components\TextInput::make('berat_badan')
                        ->label('Berat Badan')
                        ->maxLength(3)
                        ->numeric(),
                    Forms\Components\Select::make('gol_darah')
                        ->label('Golongan Darah')
                        ->options([
                            'A' => 'A',
                            'B' => 'B',
                            'AB' => 'AB',
                            'O' => 'O',
                        ]),
                ]),

            Forms\Components\Section::make('Data Keluarga')
                ->schema([
                    Forms\Components\TextInput::make('anak_ke')
                        ->label('Anak Ke')
                        ->maxLength(2)
                        ->numeric(),
                    Forms\Components\TextInput::make('dari_saudara')
                        ->label('Jumlah Saudara')
                        ->maxLength(2)
                        ->numeric(),
                ]),


            Forms\Components\Section::make('Data Ayah')
                ->schema([
                    Forms\Components\TextInput::make('nama_ayah')
                        ->maxLength(255),
                    Forms\Components\Select::make('status_hidup_ayah')
                        ->options([
                            'Hidup' => 'Hidup',
                            'Meninggal' => 'Meninggal'
                        ]),
                    Forms\Components\TextInput::make('hp_ayah')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(15),
                    Forms\Components\TextInput::make('tempat_lahir_ayah')
                        ->label('Tempat Lahir')
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('tanggal_lahir_ayah')
                        ->label('Tanggal Lahir'),
                    Forms\Components\Textarea::make('alamat_domisili_ayah')
                        ->label('Alamat Domisili')
                        ->maxLength(150),
                    Forms\Components\TextInput::make('alamat_sambung_ayah')
                        ->label('Alamat Sambung')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('pekerjaan_ayah')
                        ->label('Pekerjaan')
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
                            'Meninggal' => 'Meninggal'
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
                        ->label('Alamat Domisili')
                        ->maxLength(150),
                    Forms\Components\TextInput::make('alamat_sambung_ibu')
                        ->label('Alamat Sambung')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('pekerjaan_ibu')
                        ->label('Pekerjaan')
                        ->maxLength(50),
                ]),
        ];
    }

    public static function getColumns(): array
    {
        return [
            // Identity Numbers
            Tables\Columns\TextColumn::make('nik')
                ->label('NIK')
                ->searchable()
                ->sortable(),
            SpatieMediaLibraryImageColumn::make('foto_identitas')
                ->label('Foto Identitas')
                ->collection('siswa_foto_identitas')
                ->conversion('thumb'),
            Tables\Columns\TextColumn::make('nispn')
                ->label('NISPN')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('nama_lengkap')
                ->label('Nama Lengkap')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('nama_panggilan')
                ->label('Nama Panggilan')
                ->searchable(),
            Tables\Columns\TextColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->badge()
                ->sortable(),
            Tables\Columns\TextColumn::make('nis')
                ->label('NIS')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nisn')
                ->label('NIS')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kk')
                ->label('Nomor KK')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('rfid')
                ->label('RFID')
                ->searchable()
                ->sortable(),

            // Personal Information
            Tables\Columns\TextColumn::make('tempat_lahir')
                ->label('Tempat Lahir')
                ->searchable(),
            Tables\Columns\TextColumn::make('tanggal_lahir')
                ->label('Tanggal Lahir')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('umur')
                ->label('Umur')
                ->state(function (Siswa $record): int {
                    $tanggalLahir = $record->tanggal_lahir;
                    return Carbon::parse($tanggalLahir)->age;
                })
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('tanggal_lahir', $direction);
                }),
            // Address
            Tables\Columns\TextColumn::make('alamat')
                ->label('Alamat')
                ->wrap(50)
                ->searchable(),
            Tables\Columns\TextColumn::make('rt')
                ->label('RT')
                ->toggleable(),
            Tables\Columns\TextColumn::make('rw')
                ->label('RW')
                ->toggleable(),
            Tables\Columns\TextColumn::make('provinsi.nama')
                ->label('Provinsi')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kota.nama')
                ->label('Kota')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kecamatan.nama')
                ->label('Kecamatan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kelurahan.nama')
                ->label('Kelurahan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('kode_pos')
                ->label('Kode Pos')
                ->toggleable(),

            // Contact
            Tables\Columns\TextColumn::make('hp')
                ->label('Nomor HP')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->toggleable(),

            // Education
            Tables\Columns\TextColumn::make('pendidikan')
                ->label('Pendidikan')
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('jurusan')
                ->label('Jurusan')
                ->searchable()
                ->toggleable(),

            // Region Data
            Tables\Columns\TextColumn::make('daerahSambung.n_daerah')
                ->label('Daerah Sambung')
                ->searchable(),
            Tables\Columns\TextColumn::make('desa_sambung')
                ->label('Desa Sambung')
                ->searchable(),
            Tables\Columns\TextColumn::make('kelompok_sambung')
                ->label('Kelompok Sambung')
                ->searchable(),

            // Family Information
            Tables\Columns\TextColumn::make('anak_ke')
                ->label('Anak Ke')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('dari_saudara')
                ->label('Jumlah Saudara')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('status_nikah')
                ->label('Status Nikah')
                ->badge()
                ->searchable()
                ->sortable()
                ->toggleable(),

            // Health Information
            Tables\Columns\TextColumn::make('riwayat_sakit')
                ->label('Riwayat Sakit')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('alergi')
                ->label('Alergi')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tinggi_badan')
                ->label('Tinggi Badan')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('berat_badan')
                ->label('Berat Badan')
                ->numeric()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('gol_darah')
                ->label('Gol Darah')
                ->badge()
                ->toggleable(),

            // Additional Information
            Tables\Columns\TextColumn::make('status_mondok')
                ->label('Status Mondok')
                ->badge()
                ->searchable(),
            Tables\Columns\TextColumn::make('daerahKiriman.n_daerah')
                ->label('Daerah Kiriman')
                ->badge()
                ->searchable(),
            Tables\Columns\TextColumn::make('dapukan')
                ->label('Dapukan')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('bahasa_makna')
                ->label('Bahasa Makna')
                ->badge()
                ->searchable(),
            Tables\Columns\TextColumn::make('bahasa_harian')
                ->label('Bahasa Harian')
                ->toggleable(),
            Tables\Columns\TextColumn::make('khatam_hb')
                ->label('Khatam HB')
                ->toggleable(),
            Tables\Columns\TextColumn::make('keahlian')
                ->label('Keahlian')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('hobi')
                ->label('Hobi')
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('sim')
                ->label('SIM')
                ->badge()
                ->toggleable(),

            // Father's Information
            Tables\Columns\TextColumn::make('nama_ayah')
                ->label('Nama Ayah')
                ->searchable(),
            Tables\Columns\TextColumn::make('status_hidup_ayah')
                ->label('Status Ayah')
                ->badge(),
            Tables\Columns\TextColumn::make('hp_ayah')
                ->label('Nomor HP Ayah')
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
                ->searchable(),
            Tables\Columns\TextColumn::make('status_hidup_ibu')
                ->label('Status Ibu')
                ->badge(),
            Tables\Columns\TextColumn::make('hp_ibu')
                ->label('Nomor HP Ibu')
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
