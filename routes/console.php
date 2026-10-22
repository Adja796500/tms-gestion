<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Taches automatiques. Le serveur doit lancer "php artisan schedule:run" chaque
// minute (Planificateur de taches Windows ou cron) : voir le README.
Schedule::command('sauvegarde:automatique')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('relances:envoyer')->dailyAt('08:00')->withoutOverlapping();
