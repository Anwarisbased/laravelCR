<?php

namespace App\Filament\Resources\QrCodeGenerationSessionResource\Pages;

use App\Filament\Resources\QrCodeGenerationSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQrCodeGenerationSession extends EditRecord
{
    protected static string $resource = QrCodeGenerationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
