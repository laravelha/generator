<?php

namespace Laravelha\Generator;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laravelha\Generator\Commands\BreadcrumbCommand;
use Laravelha\Generator\Commands\ControllerCommand;
use Laravelha\Generator\Commands\CrudGenerator;
use Laravelha\Generator\Commands\FactoryCommand;
use Laravelha\Generator\Commands\FromExistingCommand;
use Laravelha\Generator\Commands\LangCommand;
use Laravelha\Generator\Commands\MigrationCommand;
use Laravelha\Generator\Commands\MigrationPivotCommand;
use Laravelha\Generator\Commands\ModelCommand;
use Laravelha\Generator\Commands\NavCommand;
use Laravelha\Generator\Commands\PackageCommand;
use Laravelha\Generator\Commands\RequestsCommand;
use Laravelha\Generator\Commands\ResourcesCommand;
use Laravelha\Generator\Commands\RouteCommand;
use Laravelha\Generator\Commands\TestsCommand;
use Laravelha\Generator\Commands\ViewCommand;

class GeneratorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/ha-generator.php' => config_path('ha-generator.php'),
        ], 'ha-generator');

        $this->publishes([
            __DIR__.'/stubs' => base_path('stubs/ha-generator'),
        ], 'ha-generator');
    }

    /**
     * Register the package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/ha-generator.php',
            'ha-generator'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                BreadcrumbCommand::class,
                ControllerCommand::class,
                CrudGenerator::class,
                FactoryCommand::class,
                FromExistingCommand::class,
                LangCommand::class,
                MigrationCommand::class,
                MigrationPivotCommand::class,
                ModelCommand::class,
                NavCommand::class,
                PackageCommand::class,
                RequestsCommand::class,
                ResourcesCommand::class,
                RouteCommand::class,
                TestsCommand::class,
                ViewCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            BreadcrumbCommand::class,
            ControllerCommand::class,
            CrudGenerator::class,
            FactoryCommand::class,
            FromExistingCommand::class,
            LangCommand::class,
            MigrationCommand::class,
            MigrationPivotCommand::class,
            ModelCommand::class,
            NavCommand::class,
            PackageCommand::class,
            RequestsCommand::class,
            ResourcesCommand::class,
            RouteCommand::class,
            TestsCommand::class,
            ViewCommand::class,
        ];
    }
}
