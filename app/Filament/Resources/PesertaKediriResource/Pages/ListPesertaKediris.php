<?php

namespace App\Filament\Resources\PesertaKediriResource\Pages;

use App\Filament\Resources\PesertaKediriResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPesertaKediris extends ListRecords
{
    protected static string $resource = PesertaKediriResource::class;

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
            'Pendaftaran ('.$periodePendaftaran.')' => Tab::make()->query(fn ($query) => $query->where('id_periode', $periodePendaftaran)),
            'Periode Lain' => Tab::make()->query(fn ($query) => $query->whereNotIn('id_periode', [$periodePendaftaran, $periodePengetesan])),
        ];
        return $tabs;
    }
}
