<?php

namespace PhoneCom\Sdk;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use PhoneCom\Sdk\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use PhoneCom\Sdk\Eloquent\QueueEntityResolver;
use PhoneCom\Sdk\Connectors\ConnectionFactory;
use PhoneCom\Sdk\Eloquent\Factory as EloquentFactory;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([__DIR__ . '/../config/phonecom-api.php' => config_path('phonecom-api.php')]);
        }

        Model::setConnectionResolver($this->app['api']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/phonecom-api.php', 'phonecom-ap');

        Model::clearBootedModels();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();

        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('api.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('api', function ($app) {
            return new ApiManager($app, $app['api.factory']);
        });
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * @return void
     */
    protected function registerEloquentFactory()
    {
        $this->app->singleton(FakerGenerator::class, function () {
            return FakerFactory::create();
        });

        $this->app->singleton(EloquentFactory::class, function ($app) {
            $faker = $app->make(FakerGenerator::class);

            return EloquentFactory::construct($faker, database_path('factories'));
        });
    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->app->singleton('Illuminate\Contracts\Queue\EntityResolver', function () {
            return new QueueEntityResolver;
        });
    }
}
