<?php

namespace Jsql\LaravelPlugin;

use Illuminate\Support\ServiceProvider;

class JsqlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/jsql.php' => config_path('jsql.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes/web.php';
        $this->app->make('Jsql\LaravelPlugin\JsqlController');
    }
}
