<?php

namespace Blare\Lamula\Providers;

use Blare\Lamula\Console\Commands\DeepL;
use Illuminate\Support\ServiceProvider;

class LamulaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        // Registre Commandss
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeepL::class,
            ]);
        }

    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Vincula clases en el contenedor de servicios si es necesario
    }
}