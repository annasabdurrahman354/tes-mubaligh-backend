<?php

namespace App\Models;

use App\Enums\BulanNomor;
use App\Enums\StatusPeriode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class Periode extends Model
{
    use HasFactory;

    protected $table = 'tb_periode';

    protected $primaryKey = 'id_periode';

    public $incrementing = false;

    protected $fillable = [
        'id_periode',
        'bulan',
        'tahun',
        'status',
    ];

    protected $casts = [
        'bulan' => BulanNomor::class,
    ];

    public static function getColumns()
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->searchable()
                ->sortable(),
            TextColumn::make('bulan')
                ->label('Bulan')
                ->badge()
                ->searchable()
                ->sortable(),
            TextColumn::make('tahun')
                ->label('Tahun')
                ->searchable()
                ->sortable(),
            SelectColumn::make('status')
                ->label('Status')
                ->options(StatusPeriode::class)
                ->searchable()
                ->sortable(),
        ];
    }

    public static function getForm()
    {
        return [
            TextInput::make('id')
                ->label('ID')
                ->disabled()
                ->dehydrated(),
            Select::make('bulan')
                ->label('Bulan')
                ->options(BulanNomor::class)
                ->required()
                ->afterStateUpdated(fn(Get $get, Set $set) => $set('id', $get('tahun').$get('bulan'))),
            TextInput::make('tahun')
                ->label('Tahun')
                ->required()
                ->numeric()
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                    return $rule
                        ->where('bulan', $get('bulan'))
                        ->where('tahun', $state);
                })
                ->afterStateUpdated(fn(Get $get, Set $set) => $set('id', $get('tahun').$get('bulan'))),
            ToggleButtons::make('status')
                ->label('Status Periode')
                ->options(StatusPeriode::class)
        ];
    }
}
