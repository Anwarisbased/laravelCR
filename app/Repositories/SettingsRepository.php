<?php
namespace App\Repositories;

use App\DTO\SettingsDTO;
use App\Infrastructure\WordPressApiWrapperInterface;
use App\Domain\MetaKeys;

final class SettingsRepository {
    private ?SettingsDTO $settingsCache = null;

    public function __construct(private WordPressApiWrapperInterface $wp) {}

    public function getSettings(): SettingsDTO {
        if ($this->settingsCache !== null) {
            return $this->settingsCache; // Return from in-request cache
        }

        $options = $this->wp->getOption(MetaKeys::MAIN_OPTIONS, []);
        
        $dto = new SettingsDTO(
            frontendUrl: $options['frontend_url'] ?? config('app.url', 'http://localhost'),
            supportEmail: $options['support_email'] ?? config('mail.from.address', 'noreply@example.com'),
            welcomeRewardProductId: (int) ($options['welcome_reward_product'] ?? config('cannarewards.welcome_reward_product_id', 0)),
            referralSignupGiftId: (int) ($options['referral_signup_gift'] ?? config('cannarewards.referral_signup_gift_id', 0)),
            referralBannerText: $options['referral_banner_text'] ?? config('cannarewards.referral_banner_text', ''),
            pointsName: $options['points_name'] ?? config('cannarewards.points_name', 'Points'),
            rankName: $options['rank_name'] ?? config('cannarewards.rank_name', 'Rank'),
            welcomeHeaderText: $options['welcome_header'] ?? config('cannarewards.welcome_header', 'Welcome, {firstName}'),
            scanButtonCta: $options['scan_cta'] ?? config('cannarewards.scan_cta', 'Scan Product')
        );

        $this->settingsCache = $dto; // Cache for the remainder of the request
        return $dto;
    }
}