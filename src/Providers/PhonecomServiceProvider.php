<?php namespace PhoneCom\Sdk\Providers;

use Illuminate\Support\ServiceProvider;
use PhoneCom\Sdk\Client;
use PhoneCom\Sdk\Models\Model;

class PhonecomServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/phonecom.php', 'phonecom');

        $this->app->singleton('phonecom', function ($app) {
            return new Client(config('phonecom'));
        });
    }

    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([__DIR__ . '/../../config/phonecom.php' => config_path('phonecom.php')]);
        }

        Model::setClient($this->app['phonecom']);
    }
}
