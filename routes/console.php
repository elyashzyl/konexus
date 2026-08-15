<?php

use App\Services\SubscriptionCheckService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Part 10 – Subscription maintenance. Runs the lightweight scheduled engine
 * that handles expirations, grace periods, overdue invoices and usage
 * snapshots without running expensive checks on page requests.
 */
Schedule::call(fn () => app(SubscriptionCheckService::class)->run())
    ->daily()
    ->name('subscription-checks');

/**
 * Online enrollment maintenance. Purges unfinished enrollment applications
 * after the retention window per the Data Privacy notice on the public form.
 */
Schedule::command('enrollments:purge-abandoned')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->name('enrollments-purge-abandoned');
