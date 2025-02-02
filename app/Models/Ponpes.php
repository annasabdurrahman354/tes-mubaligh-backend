<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ponpes extends Model
{
    use HasFactory;

    protected $table = 'tb_ponpes';

    protected $primaryKey = 'id_ponpes';

    protected $fillable = [
        'id_daerah',
        'n_ponpes',
        'status',
    ];

    public function daerah()
    {
        return $this->belongsTo(Daerah::class, 'id_daerah', 'id_daerah');
    }

    public function pesertaKediri()
    {
        return $this->hasMany(PesertaKediri::class, 'ponpes_id', 'id_ponpes');
    }

    public function pesertaKertosono()
    {
        return $this->hasMany(PesertaKertosono::class, 'ponpes_id', 'id_ponpes');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'ponpes_id', 'id_ponpes');
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Select::make('id_daerah')
                ->label('Daerah')
                ->relationship('daerah', 'n_daerah')
                ->preload()
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('n_ponpes')
                ->label('Nama Ponpes')
                ->required()
                ->maxLength(100),

            Forms\Components\ToggleButtons::make('status')
                ->label('Status')
                ->options([
                    null => '-',
                    'boarding' => "Boarding",
                    'ppm' => "PPM",
                    'pppm' => "PPPM",
                    'pusat' => "Pusat",
                    'reguler' => "Reguler",
                ]),
        ];
    }

    public static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id_ponpes')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('daerah.n_daerah')
                ->label('Nama Daerah')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('n_ponpes')
                ->label('Nama Ponpes')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->sortable()
                ->searchable(),
        ];
    }
}
