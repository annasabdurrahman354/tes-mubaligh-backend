<?php

namespace App\Filament\Resources\DaerahResource\Pages;

use App\Filament\Resources\DaerahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDaerah extends EditRecord
{
    protected static string $resource = DaerahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
