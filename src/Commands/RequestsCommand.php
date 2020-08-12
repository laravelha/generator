<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\RequestSyntaxBuilder;

class RequestsCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:requests
        {name : Table name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new requests class and apply schema at the same time';

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

        if ($directoryExists = $this->files->exists($path = $this->getPath('Store'))) {
            $this->error('StoreRequest already exists!');
        }

        if ($directoryExists = $this->files->exists($path = $this->getPath('Update'))) {
            $this->error('UpdateRequest already exists!');
        }

        if (!$directoryExists)
            $this->makeDirectory($path);

        if (! $this->files->exists($path = $this->getPath('Store'))) {

            $this->files->put($path, $this->compileStub());

            $filename = pathinfo($path, PATHINFO_FILENAME);
            $this->line("<info>Created Request:</info> {$filename}");
        }

        if (! $this->files->exists($path = $this->getPath('Update'))) {

            $this->files->put($path, $this->compileStub('update'));

            $filename = pathinfo($path, PATHINFO_FILENAME);
            $this->line("<info>Created Request:</info> {$filename}");
        }

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the requests.
     *
     * @param  array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            $this->makeDirectory($path = $this->packagePath . "/src/Http/Requests/{$this->modelName}{$args[0]}Request.php");
            return $path;
        }

        return app_path("Http/Requests/{$this->modelName}{$args[0]}Request.php");
    }

    /**
     * Compile the requests stub.
     *
     * @param String $type
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(string $type = 'store'): string
    {
        $stub = $this->files->get($this->resolveStubPath("/app/Http/Requests/Request.stub"));

        $this
            ->replaceSchema($stub)
            ->replaceClassTypeName($stub, $type)
            ->replaceNamespace($stub)
            ->replaceModelName($stub);

        return $stub;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param string $stub
     * @param string $type
     * @return RequestsCommand
     */
    protected function replaceClassTypeName(string &$stub, string $type = 'Store'): RequestsCommand
    {
        $stub = str_replace('{{type}}', ucwords($type), $stub);

        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string  $stub
     * @return RequestsCommand
     */
    protected function replaceSchema(string &$stub): RequestsCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
        }

        $stub = (new RequestSyntaxBuilder)->create($schema);

        return $this;
    }
}
