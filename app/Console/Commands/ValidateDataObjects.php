<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ValidateDataObjects extends Command
{
    protected $signature = 'data:validate';
    protected $description = 'Validate all Data objects for compliance with standards';

    public function handle()
    {
        $this->info('Validating Data objects...');
        
        // Run the Data object tests
        $this->call('test', [
            '--filter' => 'DataObjectsTest'
        ]);
        
        // Additional validation: check for proper fromModel implementations
        $this->validateFromModelMethods();
        
        $this->info('Data object validation completed.');
    }
    
    private function validateFromModelMethods()
    {
        // Check all Data objects for proper fromModel implementation
        $dataObjects = [
            app_path('Data/UserData.php'),
            app_path('Data/RankData.php'),
            app_path('Data/AchievementData.php'),
            app_path('Data/OrderData.php'),
            app_path('Data/Catalog/ProductData.php'),
            app_path('Data/Catalog/CategoryData.php'),
            app_path('Data/Catalog/EligibilityData.php'),
        ];
        
        foreach ($dataObjects as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check if fromModel method exists
                if (strpos($content, 'public static function fromModel') === false) {
                    if (basename($file) !== 'EligibilityData.php') { // This one has from() instead
                        $this->error("Data object {$file} is missing fromModel method");
                    }
                }
                
                // Check for try-catch in fromModel
                if (preg_match('/public static function fromModel.*?{.*?try.*?catch/s', $content) === 0) {
                    if (basename($file) !== 'EligibilityData.php') { // This one doesn't have fromModel
                        $this->error("Data object {$file} fromModel method should have try-catch block");
                    }
                }
            }
        }
    }
}