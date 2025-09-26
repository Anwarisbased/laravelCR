<?php

namespace App\Filament\Resources\TriggerResource\Pages;

use App\Filament\Resources\TriggerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrigger extends EditRecord
{
    protected static string $resource = TriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
