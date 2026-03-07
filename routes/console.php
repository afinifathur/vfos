<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\InvestmentUpdateService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Manual / scheduled command — uses InvestmentUpdateService
Artisan::command('stocks:update', function () {
    $this->info('Updating stock prices...');
    $service = new InvestmentUpdateService();
    $result  = $service->updateAll(); // null = update ALL users
    $this->info("Done! Updated: {$result['updated']}, Failed: {$result['failed']}, Skipped: {$result['skipped']}");
})->purpose('Update stock prices from Yahoo Finance & Kontan');

// Auto-schedule on weekdays at 09:05 and 16:05
Schedule::command('stocks:update')->weekdays()->at('09:05');
Schedule::command('stocks:update')->weekdays()->at('16:05');
