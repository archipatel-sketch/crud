<?php

namespace ArchipatelSketch\Crud\Providers;

use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'crud');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../Config/form-fields.php' => config_path('form-fields.php'),
        ], 'crud-config');

        // Publish views (optional)
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/crud'),
        ], 'crud-views');

        // Publish migrations (optional)
        $this->publishes([
            __DIR__ . '/../Database/migrations' => database_path('migrations'),
        ], 'crud-migrations');

        // Register console commands
        $this->registerCommands();
    }

    public function register()
    {
        // Merge default config so package works even if not published
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/form-fields.php',
            'form-fields'
        );
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            // Add any commands here
        ]);
    }
}
