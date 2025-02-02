<?php

namespace App\Filament\Resources\AkademikKertosonoResource\Pages;

use App\Filament\Resources\AkademikKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAkademikKertosono extends ViewRecord
{
    protected static string $resource = AkademikKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
