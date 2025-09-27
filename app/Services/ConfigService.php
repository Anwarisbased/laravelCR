<?php
namespace App\Services;

use App\Infrastructure\WordPressApiWrapperInterface;
use App\Settings\GeneralSettings; // <-- Import the new settings class

/**
 * Config Service
 *
 * Gathers all static, global configuration data for the application.
 */
class ConfigService {
    private RankService $rankService;
    private WordPressApiWrapperInterface $wp;
    private GeneralSettings $settings; // <-- Change property type

    public function __construct(
        RankService $rankService, 
        WordPressApiWrapperInterface $wp,
        GeneralSettings $settings // <-- Inject the new settings class
    ) {
        $this->rankService = $rankService;
        $this->wp = $wp;
        $this->settings = $settings; // <-- Assign it
    }

    public function getWelcomeRewardProductId(): int {
        try {
            return $this->settings->welcomeRewardProductId ?? 0;
        } catch (\Spatie\LaravelSettings\Exceptions\MissingSettings $e) {
            // If settings don't exist, return a default value
            return 0;
        }
    }

    public function getReferralSignupGiftId(): int {
        try {
            return $this->settings->referralSignupGiftId ?? 0;
        } catch (\Spatie\LaravelSettings\Exceptions\MissingSettings $e) {
            // If settings don't exist, return a default value
            return 0;
        }
    }

    public function canUsersRegister(): bool {
        return (bool) $this->wp->getOption('users_can_register');
    }

    public function areTermsAndConditionsEnabled(): bool {
        // For now, return true to require terms and conditions
        // This could be made configurable via WordPress options in the future
        return true;
    }

    public function isRegistrationEnabled(): bool {
        return $this->canUsersRegister();
    }

    /**
     * Assembles the complete application configuration object for the frontend.
     */
    public function get_app_config(): array {
        try {
            $brandPersonality = [
                'points_name'    => $this->settings->pointsName,
                'rank_name'      => $this->settings->rankName,
                'welcome_header' => $this->settings->welcomeHeaderText,
                'scan_cta'       => $this->settings->scanButtonCta,
            ];
        } catch (\Spatie\LaravelSettings\Exceptions\MissingSettings $e) {
            // If settings don't exist, return default values
            $brandPersonality = [
                'points_name'    => 'Points',
                'rank_name'      => 'Rank',
                'welcome_header' => 'Welcome, {firstName}',
                'scan_cta'       => 'Scan Product',
            ];
        }

        return [
            'settings'         => [
                'brand_personality' => $brandPersonality,
                'theme'             => [
                    'primaryFont'        => $this->get_options()['theme_primary_font'] ?? null,
                    'radius'             => $this->get_options()['theme_radius'] ?? null,
                    'background'         => $this->get_options()['theme_background'] ?? null,
                    'foreground'         => $this->get_options()['theme_foreground'] ?? null,
                    'card'               => $this->get_options()['theme_card'] ?? null,
                    'primary'            => $this->get_options()['theme_primary'] ?? null,
                    'primary-foreground' => $this->get_options()['theme_primary_foreground'] ?? null,
                    'secondary'          => $this->get_options()['theme_secondary'] ?? null,
                    'destructive'        => $this->get_options()['theme_destructive'] ?? null,
                ],
            ],
            'all_ranks'        => $this->get_all_ranks(),
            'all_achievements' => $this->get_all_achievements(),
        ];
    }

    private function get_options(): array {
        static $options_cache = [];
        if (empty($options_cache)) {
            $options_cache = $this->wp->getOption('canna_rewards_options', []);
        }
        return $options_cache;
    }

    private function get_all_ranks(): array {
        $rank_dtos = $this->rankService->getRankStructure();
        $ranks_for_api = [];
        foreach ($rank_dtos as $dto) {
            $rank_array = (array) $dto;
            $rank_array['benefits'] = [];
            $ranks_for_api[(string)$dto->key] = $rank_array;
        }
        return $ranks_for_api;
    }

    private function get_all_achievements(): array {
        $cached_achievements = $this->wp->getTransient('canna_all_achievements_v2');
        if ( is_array($cached_achievements) ) {
            return $cached_achievements;
        }

        $table_name = 'achievements';
        $results = $this->wp->dbGetResults("SELECT achievement_key, title, description, rarity, icon_url FROM `{$this->wp->db->prefix}{$table_name}` WHERE is_active = 1");

        $achievements = [];
        if ( ! empty($results) ) {
            foreach ( $results as $ach ) {
                $achievements[ $ach->achievement_key ] = [
                    'title'       => $ach->title,
                    'description' => $ach->description,
                    'rarity'      => $ach->rarity,
                    'icon_url'    => $ach->icon_url,
                ];
            }
        }
        
        $this->wp->setTransient('canna_all_achievements_v2', $achievements, 12 * HOUR_IN_SECONDS);
        return $achievements;
    }
}