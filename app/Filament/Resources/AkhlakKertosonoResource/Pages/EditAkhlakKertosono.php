<?php

namespace App\Filament\Resources\AkhlakKertosonoResource\Pages;

use App\Filament\Resources\AkhlakKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkhlakKertosono extends EditRecord
{
    protected static string $resource = AkhlakKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
