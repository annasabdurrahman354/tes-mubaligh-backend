<?php

namespace App\Models;

use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provinsi extends Model
{
    use HasFactory;

    protected $table = 'tb_provinsi';

    protected $primaryKey = 'id_provinsi';

    protected $fillable = [
        'nama',
    ];

    public function kota(): HasMany
    {
        return $this->hasMany(Kota::class, 'provinsi_id', 'id_provinsi');
    }

    public static function getColumns()
    {
        return [
            TextColumn::make('id_provinsi')
                ->label('ID')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            TextColumn::make('nama')
                ->label('Nama')
                ->sortable()
                ->searchable(),
        ];
    }

    public static function getForm()
    {
        return [
            TextInput::make('nama')
                ->label('Nama')
                ->required(),
            TableRepeater::make('kota')
                ->label('Kota')
                ->relationship('kota')
                ->default([])
                ->headers([
                    Header::make('Nama Kota')
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
