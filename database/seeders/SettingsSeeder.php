<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed if the settings do not already exist.
        $exists = DB::table('settings')
            ->where('group', 'general')
            ->where('name', GeneralSettings::class)
            ->exists();

        if (!$exists) {
            $settings = new GeneralSettings();
            $settings->frontendUrl = 'http://localhost';
            $settings->supportEmail = 'support@example.com';
            $settings->welcomeRewardProductId = 204;
            $settings->referralSignupGiftId = 1; // Default value, adjust as needed
            $settings->referralBannerText = '🎁 Earn More By Inviting Your Friends';
            $settings->pointsName = 'Points';
            $settings->rankName = 'Rank';
            $settings->welcomeHeaderText = 'Welcome, {firstName}';
            $settings->scanButtonCta = 'Scan Product';
            $settings->save();
        }
    }
}
