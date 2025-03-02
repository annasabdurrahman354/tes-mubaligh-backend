<?php

namespace App\Filament\Resources\PesertaKertosonoResource\Pages;

use App\Filament\Resources\PesertaKertosonoResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPesertaKertosonos extends ListRecords
{
    protected static string $resource = PesertaKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $periodePendaftaran = getPeriodePendaftaran();
        $periodePengetesan = getPeriodeTes();

        $tabs = [
            'Pengetesan ('.$periodePengetesan.')' => Tab::make()->query(fn ($query) => $query->where('id_periode', $periodePengetesan)),
            'Periode Lain' => Tab::make()->query(fn ($query) => $query->whereNotIn('id_periode', [$periodePengetesan])),
        ];
        return $tabs;
    }
}
