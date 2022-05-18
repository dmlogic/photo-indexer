<?php

namespace DmLogic\GooglePhotoIndex\Providers;

use Illuminate\Support\ServiceProvider;
use DmLogic\GooglePhotoIndex\Commands\IndexPhotos;
use DmLogic\GooglePhotoIndex\Commands\ReIndexAlbum;

class GooglePhotoIndexProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     */
    public function boot()
    {
        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexPhotos::class,
                ReIndexAlbum::class,
            ]);
        }

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        // Config
        $creds = json_decode(file_get_contents(__DIR__ . '/../../credentials.json'), true);
        config(['oauth' => $creds['web']]);
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
