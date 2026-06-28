<?php

namespace App\Jobs;

use App\Services\RelanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job planifié (scheduler) : envoie les relances arrivées à échéance.
 */
class EnvoyerRelancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(RelanceService $relances): void
    {
        $relances->traiterRelancesDues();
    }
}
