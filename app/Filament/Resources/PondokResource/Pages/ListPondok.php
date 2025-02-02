<?php

namespace App\Filament\Resources\PondokResource\Pages;

use App\Filament\Resources\PonpesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPondok extends ListRecords
{
    protected static string $resource = PonpesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
