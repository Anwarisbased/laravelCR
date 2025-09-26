<?php

namespace App\Filament\Resources\QrCodeGenerationSessionResource\Pages;

use App\Filament\Resources\QrCodeGenerationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQrCodeGenerationSessions extends ListRecords
{
    protected static string $resource = QrCodeGenerationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
