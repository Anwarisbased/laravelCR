<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Settings\GeneralSettings;

class InitializeSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialize-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize application settings with default values';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing application settings...');
        
        try {
            // Create new settings instance and save defaults
            $settings = app(GeneralSettings::class);
            
            // Set default values explicitly
            $settings->frontendUrl = config('app.url', 'http://localhost');
            $settings->supportEmail = 'support@example.com';
            $settings->welcomeRewardProductId = null;
            $settings->referralSignupGiftId = null;
            $settings->referralBannerText = '🎁 Earn More By Inviting Your Friends';
            $settings->pointsName = 'Points';
            $settings->rankName = 'Rank';
            $settings->welcomeHeaderText = 'Welcome, {firstName}';
            $settings->scanButtonCta = 'Scan Product';
            
            $settings->save();
            
            $this->info('Settings initialized successfully!');
            return 0; // Success
        } catch (\Exception $e) {
            $this->error('Error initializing settings: ' . $e->getMessage());
            return 1; // Error
        }
    }
}
