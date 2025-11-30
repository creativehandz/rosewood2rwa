<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic Google Sheets sync for payments
Schedule::command('payments:sync-sheets bidirectional')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/payment-sync.log'))
    ->emailOutputOnFailure('admin@rosewood.com')
    ->description('Sync payments with Google Sheets bidirectionally');

// Alternative: Schedule sync every hour during business hours
Schedule::command('payments:sync-sheets bidirectional')
    ->hourly()
    ->between('8:00', '20:00')
    ->weekdays()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/payment-sync.log'))
    ->description('Business hours payment sync with Google Sheets');

// 🎯 AUTOMATIC MONTHLY PAYMENT GENERATION WITH CARRY-FORWARD
Schedule::command('payments:generate-monthly')
    ->monthlyOn(1, '06:00')  // 1st of every month at 6 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/monthly-payments.log'))
    ->emailOutputOnFailure('admin@rosewood.com')
    ->description('Auto-generate monthly maintenance payments with carry-forward logic');

// Optional: Generate next month's payments a few days early for planning
Schedule::command('payments:generate-monthly', [now()->addMonth()->format('Y-m')])
    ->monthlyOn(28, '18:00')  // 28th of month at 6 PM (generate next month)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/advance-payments.log'))
    ->description('Pre-generate next month payments for advance planning');
