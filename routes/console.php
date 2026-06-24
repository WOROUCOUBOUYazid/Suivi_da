<?php

use App\Jobs\EnvoyerRelancesJob;
use App\Services\RelanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Envoi quotidien des relances arrivées à échéance.
Schedule::job(new EnvoyerRelancesJob)->dailyAt('08:00');

// Commande manuelle pour déclencher les relances (tests / exploitation).
Artisan::command('relances:envoyer', function () {
    $nb = app(RelanceService::class)->traiterRelancesDues();
    $this->info("{$nb} relance(s) envoyée(s).");
})->purpose('Envoie les relances arrivées à échéance');
