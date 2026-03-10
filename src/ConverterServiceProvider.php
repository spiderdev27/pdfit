<?php

namespace Veoksha\LaravelUniversalConverter;

use Illuminate\Support\ServiceProvider;

class ConverterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/converter.php', 'converter');

        $this->app->singleton(Converter::class, function ($app) {
            return new Converter(
                uvPath: config('converter.uv_path'),
                pythonScriptPath: __DIR__ . '/../python/convert.py',
                timeout: config('converter.timeout', 120)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/converter.php' => config_path('converter.php'),
            ], 'converter-config');

            $this->commands([
                Console\ConverterCheckCommand::class,
            ]);
        }
    }
}
