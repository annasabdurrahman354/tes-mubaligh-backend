<?php

namespace App\Filament\Resources\PesertaKertosonoResource\Pages;

use App\Filament\Resources\PesertaKertosonoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPesertaKertosono extends EditRecord
{
    protected static string $resource = PesertaKertosonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
