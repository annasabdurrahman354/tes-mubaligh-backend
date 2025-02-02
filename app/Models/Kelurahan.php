<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelurahan extends Model
{
    use HasFactory;

    protected $table = 'tb_desa_kel';

    protected $primaryKey = 'id_desa_kel';

    protected $fillable = [
        'nama',
        'kecamatan_id',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id_kecamatan');
    }

    public static function getColumns()
    {
        return[
                TextColumn::make('id_desa_kel')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kecamatan.nama')
                    ->label('Kecamatan')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kecamatan.kota.nama')
                    ->label('Kota')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kecamatan.kota.provinsi.nama')
                    ->label('Provinsi')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
            ];
    }

    public static function getForm()
    {
        return [
            Select::make('kecamatan_id')
                ->label('Kecamatan')
                ->relationship('kecamatan', 'nama')
                ->searchable()
                ->required(),
            TextInput::make('nama')
                ->label('Nama')
                ->required(),
        ];
    }
}
