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
        // Properties already have default values, no need for fillMissing
    }
}