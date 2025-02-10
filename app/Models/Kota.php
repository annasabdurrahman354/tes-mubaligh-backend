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

class Kota extends Model
{
    use HasFactory;

    protected $table = 'tb_kota_kab';
    protected $primaryKey = 'id_kota_kab';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'provinsi_id',
    ];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id_provinsi');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(Kecamatan::class, 'kota_kab_id', 'id_kota_kab');
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id_kota_kab')
                ->label('ID')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            TextColumn::make('nama')
                ->label('Nama')
                ->searchable()
                ->sortable(),
            TextColumn::make('provinsi.nama')
                ->label('Provinsi')
                ->searchable()
                ->sortable(),
        ];
    }

    public static function getForm()
    {
        return [
            Select::make('provinsi_id')
                ->label('Provinsi')
                ->relationship('provinsi', 'nama')
                ->searchable()
                ->required(),
            TextInput::make('nama')
                ->label('Nama')
                ->required(),
            TableRepeater::make('kecamatan')
                ->label('Kecamatan')
                ->relationship('kecamatan')
                ->default([])
                ->headers([
                    Header::make('Nama Kecamatan')
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
