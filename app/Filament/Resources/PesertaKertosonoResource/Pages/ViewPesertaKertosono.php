<?php

namespace App\Filament\Resources\PesertaKertosonoResource\Pages;

use App\Filament\Resources\PesertaKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPesertaKertosono extends ViewRecord
{
    protected static string $resource = PesertaKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
