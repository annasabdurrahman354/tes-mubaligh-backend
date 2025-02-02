<?php

namespace App\Filament\Resources\AkademikKediriResource\Pages;

use App\Filament\Resources\AkademikKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAkademikKediri extends ViewRecord
{
    protected static string $resource = AkademikKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
