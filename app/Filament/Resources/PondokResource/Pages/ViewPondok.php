<?php

namespace App\Filament\Resources\PondokResource\Pages;

use App\Filament\Resources\PonpesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPondok extends ViewRecord
{
    protected static string $resource = PonpesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
