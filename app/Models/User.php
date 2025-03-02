<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasMedia
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasPanelShield, SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'tes_users';

    protected $fillable = [
        'nama',
        'nama_panggilan',
        'username',
        'jenis_kelamin',
        'email',
        'nomor_telepon',
        'nik',
        'rfid',
        'ponpes_id',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'jenis_kelamin' => JenisKelamin::class,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole([\App\Enums\Role::SUPER_ADMIN->value, \App\Enums\Role::ADMIN_KEDIRI->value, \App\Enums\Role::ADMIN_KERTOSONO->value]);
    }

    protected function recordTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nama.' ('.$this->roles()->first()?->name.')',
        );
    }

    protected function urlFotoIdentitas(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('user_foto', 'thumb'),
        );
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('user_foto', 'thumb') != ''
            ? $this->getFirstMediaUrl('user_foto', 'thumb') :
            "https://ui-avatars.com/api/?background=random&size=256=true&name=".str_replace(" ", "+", $this->nama);
    }

    public function getFilamentName(): string
    {
        return $this->nama;
    }

    public function getJumlahPenyimakanPutraKediriAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId)
                    ->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where('jenis_kelamin', JenisKelamin::LAKI_LAKI);
                    });
            })
            ->count();
    }

    public function getJumlahPenyimakanPutriKediriAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKediri()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId)
                    ->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where('jenis_kelamin', JenisKelamin::PEREMPUAN);
                    });
            })
            ->count();
    }

    public function getTotalPenyimakanKediriAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKediri()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId);
            })
            ->count();
    }

    public function getJumlahPenyimakanPutraKertosonoAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId)
                    ->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where('jenis_kelamin', JenisKelamin::LAKI_LAKI);
                    });
            })
            ->count();
    }

    public function getJumlahPenyimakanPutriKertosonoAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId)
                    ->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where('jenis_kelamin', JenisKelamin::PEREMPUAN);
                    });
            })
            ->count();
    }

    public function getTotalPenyimakanKertosonoAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId);
            })
            ->count();
    }

    public function getTotalDurasiPenilaianKertosonoAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId);
            })
            ->sum('durasi_penilaian');
    }

    public function getRataRataDurasiPenilaianKertosonoAttribute()
    {
        $periodePengetesanId = getPeriodeTes();

        return $this->akademikKertosono()
            ->whereHas('peserta', function ($query) use ($periodePengetesanId) {
                $query->where('id_periode', $periodePengetesanId);
            })
            ->average('durasi_penilaian');
    }

    public function notifications()
    {
        return $this->morphMany(UserDatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function akhlakKediri()
    {
        return $this->hasMany(AkhlakKediri::class, 'guru_id');
    }

    public function akhlakKertosono()
    {
        return $this->hasMany(AkhlakKertosono::class, 'guru_id');
    }

    public function akademikKediri()
    {
        return $this->hasMany(AkademikKediri::class, 'guru_id');
    }


    public function akademikKertosono()
    {
        return $this->hasMany(AkademikKertosono::class, 'guru_id');
    }

    public function ponpes()
    {
        return $this->belongsTo(Ponpes::class, 'ponpes_id', 'id_ponpes');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 256, 256)
            ->optimize()
            ->nonQueued();
    }

    public function syncMediaName(){
        foreach($this->getMedia('user_foto') as $media){
            $media->file_name = getMediaFilename($this, $media);
            $media->save();
        }
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            SpatieMediaLibraryImageColumn::make('foto')
                ->label('Foto')
                ->collection('user_foto')
                ->conversion('thumb'),
            TextColumn::make('nama')
                ->label('Nama Lengkap')
                ->searchable()
                ->sortable(),
            TextColumn::make('nama_panggilan')
                ->label('Nama Panggilan')
                ->searchable()
                ->sortable(),
            TextColumn::make('username')
                ->label('Username')
                ->searchable()
                ->sortable(),
            TextColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->badge()
                ->searchable()
                ->sortable(),
            TextColumn::make('roles.name')
                ->label('Peran')
                ->formatStateUsing(fn ($state): string => Str::headline($state))
                ->colors(['info'])
                ->badge()
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable(),
            TextColumn::make('nomor_telepon')
                ->label('Nomor Telepon')
                ->searchable()
                ->sortable(),
            TextColumn::make('nik')
                ->label('NIK')
                ->searchable()
                ->sortable(),
            TextColumn::make('rfid')
                ->label('RFID')
                ->searchable()
                ->sortable(),
            TextColumn::make('ponpes.nama')
                ->label('Pondok Pesantren')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getForm()
    {
        return [
            SpatieMediaLibraryFileUpload::make('foto')
                ->label('Foto')
                ->avatar()
                ->collection('user_foto')
                ->conversion('thumb')
                ->moveFiles()
                ->image()
                ->imageEditor()
                ->columnSpanFull(),
            TextInput::make('nama')
                ->label('Nama Lengkap')
                ->required(),
            TextInput::make('nama_panggilan')
                ->label('Nama Panggilan'),
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule->whereNull('deleted_at');
                }),
            ToggleButtons::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->options(JenisKelamin::class)
                ->grouped()
                ->inline()
                ->required(),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule->whereNull('deleted_at');
                }),
            TextInput::make('nomor_telepon')
                ->label('Nomor Telepon')
                ->tel()
                ->maxLength(15)
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule->whereNull('deleted_at');
                }),
            TextInput::make('nik')
                ->label('Nomor Induk Kependudukan')
                ->numeric()
                ->length(16)
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule->whereNull('deleted_at');
                }),
            TextInput::make('rfid')
                ->label('RFID')
                ->numeric()
                ->maxLength(11)
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule->whereNull('deleted_at');
                }),
            Select::make('id_ponpes')
                ->label('Pondok Pesantren')
                ->relationship(
                    name: 'ponpes',
                    titleAttribute: 'n_ponpes'
                )
                ->searchable(),
            Select::make('roles')
                ->label('Role')
                ->relationship(name: 'roles', titleAttribute: 'name', modifyQueryUsing: function (Builder $query) {
                    return $query->whereNotIn('name', ['filament_user']);
                })
                ->options(fn () => DB::table(config('permission.table_names.roles'))->pluck('name', 'id'))
                ->multiple()
                ->native(false),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state)),
            DateTimePicker::make('email_verified_at')
                ->label('Verifikasi Email')
                ->default(now())
        ];
    }
}
