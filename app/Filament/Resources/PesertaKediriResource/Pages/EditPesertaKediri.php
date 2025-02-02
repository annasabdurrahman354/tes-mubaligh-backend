<?php

namespace App\Filament\Resources\PesertaKediriResource\Pages;

use App\Filament\Resources\PesertaKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPesertaKediri extends EditRecord
{
    protected static string $resource = PesertaKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
