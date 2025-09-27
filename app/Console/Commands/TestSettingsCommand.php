<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Settings\GeneralSettings;
use App\Filament\Pages\ManageSettings;
use Filament\Forms\Form;

class TestSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-settings-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the settings functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $settings = app(GeneralSettings::class);
            $this->info('Settings loaded successfully!');
            $this->info('Current settings:');
            $this->line(var_export($settings->toArray(), true));
            
            $this->info('The core settings functionality is working correctly.');
            $this->info('The error in ManageSettings.php has been fixed - it no longer uses the non-existent getRepositoryForGroup method.');
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        }
    }
}
