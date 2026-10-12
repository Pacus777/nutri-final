<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->commands([
            \App\Console\Commands\ExportFeaturesCommand::class,
            \App\Console\Commands\RegisterCandidateModelCommand::class,
            \App\Console\Commands\PredictKMeansCommand::class,
            \App\Console\Commands\CargarAlimentosBaseCommand::class,
            \App\Console\Commands\ImportarAlimentosUsdaCommand::class,
        ]);
    }
}
