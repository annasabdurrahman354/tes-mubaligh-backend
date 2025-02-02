<?php

namespace App\Models;

use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'tb_kecamatan';

    protected $primaryKey = 'id_kecamatan';

    protected $fillable = [
        'nama',
        'kota_kab_id',
    ];

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kota_kab_id', 'id_kota_kab');
    }

    public function kelurahan(): HasMany
    {
        return $this->hasMany(Kelurahan::class, 'kecamatan_id', 'id_kecamatan');
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id_kecamatan')
                ->label('ID')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            TextColumn::make('nama')
                ->label('Nama')
                ->sortable()
                ->searchable(),
            TextColumn::make('kota.nama')
                ->label('Kota')
                ->numeric()
                ->sortable()
                ->searchable(),
            TextColumn::make('kota.provinsi.nama')
                ->label('Provinsi')
                ->numeric()
                ->sortable()
                ->searchable(),
        ];
    }

    public static function getForm()
    {
        return [
            Select::make('kota_kab_id')
                ->label('Kabupaten/Kota')
                ->relationship('kota', 'nama')
                ->searchable()
                ->required(),
            TextInput::make('nama')
                ->label('Nama')
                ->required(),
            TableRepeater::make('kelurahan')
                ->label('Kelurahan')
                ->relationship('kelurahan')
                ->default([])
                ->headers([
                    Header::make('Nama Kelurahan')
                ])
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama')
                        ->required(),
                ])
                ->columnSpanFull()
        ];
    }
}
