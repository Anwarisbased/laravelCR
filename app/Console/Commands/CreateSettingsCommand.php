<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\LaravelSettings\SettingsRepository;
use App\Settings\GeneralSettings;

class CreateSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:create-defaults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default settings entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating default settings...');
        
        // Since the package fails to load settings when they don't exist,
        // we'll use a different approach by directly using the settings repository
        try {
            // Create a new instance of the settings class and populate with defaults
            $settings = new GeneralSettings();
            
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
            
            // Save the settings - this should create the proper entry in the database
            $settings->save();
            
            $this->info('Default settings created successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error creating settings: ' . $e->getMessage());
            return 1;
        }
    }
}
