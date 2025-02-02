<?php

namespace App\Filament\Resources\AkhlakKediriResource\Pages;

use App\Filament\Resources\AkhlakKediriResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListAkhlakKediris extends ListRecords
{
    protected static string $resource = AkhlakKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $periodePengetesan = getPeriodeTes();

        $tabs = [
            'Pengetesan (' . $periodePengetesan . ')' => Tab::make()->query(fn ($query) =>
            $query->whereHas('peserta', fn ($subQuery) =>
            $subQuery->where('periode_id', $periodePengetesan)
            )
            ),
            'Periode Lain' => Tab::make()->query(fn ($query) =>
            $query->whereHas('peserta', fn ($subQuery) =>
            $subQuery->where('periode_id', '!=', $periodePengetesan)
            )
            ),
        ];

        return $tabs;
    }
}
