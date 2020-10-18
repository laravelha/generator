<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\ModelSyntaxBuilder;

class ModelCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:model
        {name : Model name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--a|api : The application is an API?}
        {--d|datatables : Use to datatables}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model class and apply schema at the same time';

    /**
     * @var string
     */
    const STUB_DIR = __DIR__.'/../stubs';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function handle(): void
    {
        $this->setNames();

        if ($this->files->exists($path = $this->getPath())) {
            $this->error('Model already exists!');

            return;
        }

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Model:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the model.
     *
     * @param array $args
     *
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            $this->makeDirectory($path = $this->packagePath."/src/Models/{$this->modelName}.php");

            return $path;
        }

        return is_dir(app_path('Models')) ? app_path("Models/{$this->modelName}.php") : app_path("{$this->modelName}.php");
    }

    /**
     * Compile the model stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileStub(): string
    {
        return $stub = $this->option('api') ? $this->compileApiModelStub() : $this->compileWebModelStub();
    }

    /**
     * Compile the model api stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileApiModelStub(): string
    {
        $stub = $this->files->get($this->resolveStubPath('/app/ApiModel.stub'));

        $this
            ->replaceSchema($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub);

        return $stub;
    }

    /**
     * Compile the model web stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileWebModelStub(): string
    {
        $datatables = $this->option('datatables') ? 'Datatables' : '';

        $stub = $this->files->get($this->resolveStubPath("/app/WebModel{$datatables}.stub"));

        $this
            ->replaceSchema($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub);

        return $stub;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param string $stub
     *
     * @return ModelCommand
     */
    protected function replaceSchema(string &$stub): ModelCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser())->parse($schema);
        }

        $schema = (new ModelSyntaxBuilder())->create($schema);

        $stub = str_replace(['{{column}}', '{{foreign}}', '{{searchables}}'], $schema, $stub);

        return $this;
    }
}
