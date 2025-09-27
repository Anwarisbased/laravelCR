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
            (new GeneralSettings())->save();
        }
    }
}
