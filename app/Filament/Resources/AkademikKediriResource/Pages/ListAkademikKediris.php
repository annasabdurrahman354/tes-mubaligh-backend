<?php

namespace App\Filament\Resources\AkademikKediriResource\Pages;

use App\Filament\Resources\AkademikKediriResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListAkademikKediris extends ListRecords
{
    protected static string $resource = AkademikKediriResource::class;

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
