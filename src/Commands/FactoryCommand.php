<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\FactorySintaxeBuilder;

class FactoryCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:factory
        {name : Model name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory class and apply schema at the same time';

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

        if ($this->files->exists($path = $this->getPath())) {
            $this->error('Factory already exists!');
            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Factory:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the factory.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            return $this->packagePath . '/' . config('ha-generator.packageFactoriesFolder') . "/{$this->modelName}Factory.php";
        }

        return database_path("factories/{$this->modelName}Factory.php");
    }

    /**
     * Compile the factory stub.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(): string
    {
        $stub = $this->files->get(self::STUB_DIR . "/database/factories/factory.stub");

        $this
            ->replaceSchema($stub)
            ->replaceNamespace($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub);

        return $stub;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string  $stub
     * @return FactoryCommand
     */
    protected function replaceSchema(string &$stub): FactoryCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
        }

        $stub = (new FactorySintaxeBuilder)->create($schema);

        return $this;
    }
}
