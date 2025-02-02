<?php

namespace App\Filament\Resources\AkhlakKediriResource\Pages;

use App\Filament\Resources\AkhlakKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkhlakKediri extends EditRecord
{
    protected static string $resource = AkhlakKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
