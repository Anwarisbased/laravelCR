<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\ValidateDataObjects;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register custom commands
Artisan::command('data:validate', function () {
    $command = new ValidateDataObjects();
    $command->handle();
})->purpose('Validate all Data objects for compliance with standards');
