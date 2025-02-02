<?php

namespace App\Filament\Resources\AkhlakKediriResource\Pages;

use App\Filament\Resources\AkhlakKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAkhlakKediri extends ViewRecord
{
    protected static string $resource = AkhlakKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
