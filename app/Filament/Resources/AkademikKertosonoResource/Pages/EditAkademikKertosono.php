<?php

namespace App\Filament\Resources\AkademikKertosonoResource\Pages;

use App\Filament\Resources\AkademikKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkademikKertosono extends EditRecord
{
    protected static string $resource = AkademikKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
