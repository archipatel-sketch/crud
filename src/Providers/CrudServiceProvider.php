<?php

namespace ArchipatelSketch\Crud\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Service provider for the QueryBuilder package.
 * Handles the registration and bootstrapping of routes, views, and helper files.
 */
class CrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load the package routes from the defined web.php file.
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Wrap explicitly for web middleware (session/CSRF Token)
        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        // Load views from the package's Resources/views directory and assign a namespace.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crud');

        // Publish the package's configuration file to the application's config directory.
        // This allows users to customize package settings without modifying the core package files.
        $this->publishes([
            __DIR__.'/../config/form-fields.php' => config_path('form-fields.php'),
        ], 'form-fields');

        // configure assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/crud'),
        ], 'crud-assets');

        // Include a helpers file containing custom utility functions.
        require_once __DIR__.'/../Helpers/helpers.php';

        // Register commands in the console.
        // $this->registerCommands();

    }

    /**
     * Register any application services.
     *
     * This method is used to bind services into the service container.
     * Currently, no additional bindings are defined.
     *
     * @return void
     */
    public function register()
    {
        // This function is left empty, but can be used to register bindings.
    }
}
