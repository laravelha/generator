<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourcesCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:resources
        {name : Table name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resources class and apply schema at the same time';

    /**
     * @var string
     */
    const STUB_DIR = __DIR__ . "/../stubs";

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $this->setNames();

        if ($this->files->exists($path = $this->getPath('Resource'))) {
            $this->error('Resource already exists!');
        }

        if ($this->files->exists($path = $this->getPath('Collection'))) {
            $this->error('Collection already exists!');
        }

        $this->makeDirectory($path);

        if (! $this->files->exists($path = $this->getPath('Resource'))) {

            $this->files->put($path, $this->compileStub());

            $filename = pathinfo($path, PATHINFO_FILENAME);
            $this->line("<info>Created Resource:</info> {$filename}");
        }

        if (! $this->files->exists($path = $this->getPath('Collection'))) {

            $this->files->put($path, $this->compileStub('Collection'));

            $filename = pathinfo($path, PATHINFO_FILENAME);
            $this->line("<info>Created Collection:</info> {$filename}");
        }

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the resources.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            $this->makeDirectory($path = $this->packagePath . "/src/Http/Resources/{$this->modelName}{$args[0]}.php");
            return $path;
        }

        return app_path("Http/Resources/{$this->modelName}{$args[0]}.php");
    }


    /**
     * Compile the resources stub.
     *
     * @param String $sufix
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(string $sufix = 'Resource'): string
    {
        $stub = $this->files->get($this->resolveStubPath("/app/Http/Resources/{$sufix}.stub"));

        $this
            ->replaceNamespace($stub)
            ->replaceModelName($stub);

        return $stub;
    }
}
