<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\LangSyntaxBuilder;

class LangCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:lang
        {name : Model name (singular) for example User}
        {folder=pt-br : Lang folder}
        {--no-log : No logging}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new lang and apply schema at the same time';

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
            $this->error('Lang already exists!');
            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Lang:</info> {$this->argument('folder')}/{$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the lang.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        return resource_path("lang/{$this->argument('folder')}/{$this->tableName}.php");
    }

    /**
     * Compile the lang stub.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(): string
    {
        $stub = $this->files->get(self::STUB_DIR . "/resources/lang/lang.stub");

        if($this->option('schema')) {
            $this->replaceSchema($stub);
        }

        $stub = str_replace('{{column}}', '', $stub);

        $this->replaceModelNamePlural($stub)
            ->replaceModelName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param  string $stub
     * @return LangCommand
     */
    protected function replaceModelNamePlural(string &$stub): LangCommand
    {
        $stub = str_replace('{{modelNamePlural}}', Str::plural($this->modelName), $stub);

        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string  $stub
     * @return LangCommand
     */
    protected function replaceSchema(string &$stub): LangCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
        }

        $stub = (new LangSyntaxBuilder)->create($schema);

        return $this;
    }
}
