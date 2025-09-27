<?php

use Illuminate\Database\Migrations\Migration;
use App\Settings\GeneralSettings; // <-- Import the settings class
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The check is good, but let's make it more specific
        $exists = DB::table('settings')
            ->where('group', 'general')
            ->where('name', GeneralSettings::class) // Use the class constant for reliability
            ->exists();

        if (!$exists) {
            // Insert default general settings directly into the settings table
            // Using the same defaults as defined in the GeneralSettings::booted() method
            DB::table('settings')->insert([
                'group' => 'general',
                'name' => GeneralSettings::class, // Use the class constant for consistency
                'payload' => json_encode([
                    'frontendUrl' => Config::get('app.url', 'http://localhost'),
                    'supportEmail' => 'support@example.com',
                    'welcomeRewardProductId' => null,
                    'referralSignupGiftId' => null,
                    'referralBannerText' => 'Earn More By Inviting Your Friends',
                    'pointsName' => 'Points',
                    'rankName' => 'Rank',
                    'welcomeHeaderText' => 'Welcome, {firstName}',
                    'scanButtonCta' => 'Scan Product',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', GeneralSettings::class) // Use the class constant here too
            ->delete();
    }
};