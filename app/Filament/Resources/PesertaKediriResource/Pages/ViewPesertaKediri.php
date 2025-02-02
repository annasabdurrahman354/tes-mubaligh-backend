<?php

namespace App\Filament\Resources\PesertaKediriResource\Pages;

use App\Filament\Resources\PesertaKediriResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPesertaKediri extends ViewRecord
{
    protected static string $resource = PesertaKediriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
