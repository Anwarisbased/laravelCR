<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $frontendUrl = 'http://localhost';
    public string $supportEmail = 'support@example.com';
    public ?int $welcomeRewardProductId = null;
    public ?int $referralSignupGiftId = null;
    public string $referralBannerText = '🎁 Earn More By Inviting Your Friends';
    public string $pointsName = 'Points';
    public string $rankName = 'Rank';
    public string $welcomeHeaderText = 'Welcome, {firstName}';
    public string $scanButtonCta = 'Scan Product';

    public static function group(): string
    {
        return 'general';
    }

    public static function encrypted(): array
    {
        return [];
    }
    
    public function booted(): void
    {
        // Use fillMissing to ensure all properties have values
        $this->fillMissing([
            'frontendUrl' => 'http://localhost',
            'supportEmail' => 'support@example.com',
            'welcomeRewardProductId' => null,
            'referralSignupGiftId' => null,
            'referralBannerText' => '🎁 Earn More By Inviting Your Friends',
            'pointsName' => 'Points',
            'rankName' => 'Rank',
            'welcomeHeaderText' => 'Welcome, {firstName}',
            'scanButtonCta' => 'Scan Product',
        ]);
    }
}