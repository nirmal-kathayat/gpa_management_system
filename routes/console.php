<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Custom commands for GPA System
Artisan::command('gpa:setup', function () {
    $this->info('Setting up GPA System...');
    
    // Run migrations
    $this->call('migrate');
    
    // Run seeders
    $this->call('db:seed');
    
    // Create storage link
    $this->call('storage:link');
    
    $this->info('GPA System setup completed!');
    $this->info('Default login: admin@gpa-system.com / admin123');
})->purpose('Setup the GPA System with migrations and seeders');

Artisan::command('gpa:reset', function () {
    if ($this->confirm('This will reset all data. Are you sure?')) {
        $this->call('migrate:fresh');
        $this->call('db:seed');
        $this->info('GPA System has been reset!');
    }
})->purpose('Reset the GPA System database');
