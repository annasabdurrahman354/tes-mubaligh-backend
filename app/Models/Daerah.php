<?php

namespace App\Models;

use App\Enums\Wilayah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables;

class Daerah extends Model
{
    use HasFactory;

    protected $table = 'tb_daerah';

    protected $primaryKey = 'id_daerah';

    protected $fillable = [
        'n_daerah',
        'n_provinsi',
        'wilayah',
    ];

    protected $casts = [
        'wilayah' => Wilayah::class
    ];

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make('n_daerah')
                ->label('Nama Daerah')
                ->required(),

            Forms\Components\TextInput::make('n_provinsi')
                ->label('Nama Provinsi')
                ->required(),

            Forms\Components\ToggleButtons::make('wilayah')
                ->label('Wilayah')
                ->options(Wilayah::class)
                ->required(),
        ];
    }

    public static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id_daerah')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('n_daerah')
                ->label('Nama Daerah')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('n_provinsi')
                ->label('Nama Provinsi')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('wilayah')
                ->label('Wilayah')
                ->badge()
                ->sortable()
                ->searchable(),
        ];
    }
}
