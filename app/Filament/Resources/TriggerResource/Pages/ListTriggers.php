<?php

namespace App\Filament\Resources\TriggerResource\Pages;

use App\Filament\Resources\TriggerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTriggers extends ListRecords
{
    protected static string $resource = TriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
