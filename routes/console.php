<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('denapax:notifications:dispatch')->everyMinute()->withoutOverlapping();

Artisan::command('denapax:analytics:prune', function (): void {
    $cutoff = now('Asia/Tehran')->subDays(90)->toDateString();
    $deleted = DB::table('page_view_visitors')->where('visit_date', '<', $cutoff)->delete();
    $this->info($deleted.' expired analytics visitor record(s) removed.');
})->purpose('Prune temporary anonymous page-view identities without deleting daily totals');

Schedule::command('denapax:analytics:prune')->dailyAt('03:40')->timezone('Asia/Tehran')->withoutOverlapping();
