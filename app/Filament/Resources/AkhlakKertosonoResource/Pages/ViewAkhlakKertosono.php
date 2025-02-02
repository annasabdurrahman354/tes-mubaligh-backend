<?php

namespace App\Filament\Resources\AkhlakKertosonoResource\Pages;

use App\Filament\Resources\AkhlakKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAkhlakKertosono extends ViewRecord
{
    protected static string $resource = AkhlakKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
