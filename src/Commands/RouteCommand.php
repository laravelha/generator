<?php

namespace Laravelha\Generator\Commands;

class RouteCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:route
        {name : Model name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--d|datatables : Use to datatables}
        {--a|api : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert new resources routes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->setNames();

        if (!$this->files->exists($path = $this->getPath())) {
            $this->error('Route file not found');

            return;
        }

        $this->files->append($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Inserted Route in:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the routes.
     *
     * @param array $args
     *
     * @return string
     */
    protected function getPath(...$args): string
    {
        $name = $this->option('api') ? 'api' : 'web';

        if ($this->hasPackage()) {
            $this->makeDirectory($path = $this->packagePath.'/'.config('ha-generator.packageRoutesFolder')."/$name.php");

            return $path;
        }

        return base_path("routes/$name.php");
    }

    /**
     * Compile the route stub.
     *
     * @return string
     */
    protected function compileStub(): string
    {
        return $stub = $this->option('api') ? $this->compileApiStub() : $this->compileWebStub();
    }

    /**
     * Compile the routes api stub.
     *
     * @return string
     */
    protected function compileApiStub(): string
    {
        $stub = PHP_EOL."Route::apiResource('{{routeName}}', App\Http\Controllers\{{modelName}}Controller::class);".PHP_EOL;

        $this
            ->replaceModelName($stub)
            ->replaceRouteName($stub);

        return $stub;
    }

    /**
     * Compile the route web stub.
     *
     * @return string
     */
    protected function compileWebStub(): string
    {
        $stub = PHP_EOL;

        $stub = $this->option('datatables')
                ? $stub."Route::get('/{{routeName}}/data', [App\Http\Controllers\{{modelName}}Controller::class, 'data'])->name('{{routeName}}.data');".PHP_EOL
                : '';

        $stub = $stub.
            "Route::get('/{{routeName}}/{{{objectName}}}/delete', [App\Http\Controllers\{{modelName}}Controller::class, 'delete'])->name('{{routeName}}.delete');".PHP_EOL.
            "Route::resource('{{routeName}}', App\Http\Controllers\{{modelName}}Controller::class);".PHP_EOL;

        $this
            ->replaceModelName($stub)
            ->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }
}
