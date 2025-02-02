<?php

namespace App\Filament\Resources\AkademikKediriResource\Pages;

use App\Filament\Resources\AkademikKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkademikKediri extends EditRecord
{
    protected static string $resource = AkademikKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
