<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Surveillance météo des croisières à venir (doc/mvp-tech.md : toutes les 3 h).
Schedule::command('cruises:monitor-weather')
    ->everyThreeHours()
    ->withoutOverlapping();
