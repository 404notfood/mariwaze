<?php

namespace App\Console\Commands;

use App\Jobs\CheckCruiseWeatherJob;
use App\Models\Cruise;
use Illuminate\Console\Command;

class MonitorCruiseWeather extends Command
{
    protected $signature = 'cruises:monitor-weather {--days=14 : Fenetre de surveillance en jours}';

    protected $description = 'Programme la verification meteo des croisieres a venir.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $cruises = Cruise::query()
            ->whereNotNull('user_id')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->whereDate('departure_date', '<=', now()->addDays($days)->toDateString())
            ->has('selectedRoute')
            ->pluck('id');

        foreach ($cruises as $id) {
            CheckCruiseWeatherJob::dispatch($id);
        }

        $this->info(sprintf('%d croisiere(s) placees en file de verification meteo.', $cruises->count()));

        return self::SUCCESS;
    }
}
