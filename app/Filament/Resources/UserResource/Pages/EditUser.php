<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\ReferralService;
use Illuminate\Support\Facades\App;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_referral_code')
                ->label('Generate Referral Code (Manual)')
                ->action(function () {
                    $referralService = App::make(ReferralService::class);
                    $referralService->generate_code_for_new_user($this->record->id, $this->record->name ?: 'User');
                    $this->notify('success', 'Referral code generated successfully!');
                    $this->record->refresh();
                })
                ->visible(fn () => empty($this->record->meta['_canna_referral_code'] ?? null))
                ->requiresConfirmation()
                ->color('secondary'),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}