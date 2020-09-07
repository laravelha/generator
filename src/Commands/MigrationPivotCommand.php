<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class MigrationPivotCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:migration:pivot
        {tableOne : The name of the first table.}
        {tableTwo : The name of the second table.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration pivot class';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     * @var string
     */
    const STUB_DIR = __DIR__.'/../stubs';

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer   $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->files->exists($path = $this->getPath())) {
            $this->error('Migration already exists!');

            return;
        }

        if ($this->migrationAlreadyExist()) {
            $this->error("A {$this->getClassName()} class already exists.");

            return;
        }

        $this->files->put($path, $this->buildClass());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Migration:</info> {$filename}");

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the class name from table names.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        $name = implode('', array_map('ucwords', $this->getSortedSingularTableNames()));

        $name = preg_replace_callback('/(\_)([a-z]{1})/', function ($matches) {
            return Str::studly($matches[0]);
        }, $name);

        return "Create{$name}PivotTable";
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/database/migrations/pivot.stub');
    }

    /**
     * @return mixed
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = base_path('stubs/ha-generator/'.trim($stub, '/'))) && config('ha-generator.customStubs')
            ? $customPath
            : static::STUB_DIR.$stub;
    }

    /**
     * Get the destination class path.
     *
     * @return string
     */
    protected function getPath(): string
    {
        return base_path().'/database/migrations/'.date('Y_m_d_His').
            '_create_'.$this->getPivotTableName().'_pivot_table.php';
    }

    /**
     * Build the class with the given name.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function buildClass(): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replacePivotTableName($stub)
            ->replaceSchema($stub)
            ->replaceClass($stub, $this->getClassName());
    }

    /**
     * Apply the name of the pivot table to the stub.
     *
     * @param string $stub
     *
     * @return MigrationPivotCommand
     */
    protected function replacePivotTableName(&$stub): MigrationPivotCommand
    {
        $stub = str_replace('{{pivotTableName}}', $this->getPivotTableName(), $stub);

        return $this;
    }

    /**
     * Apply the correct schema to the stub.
     *
     * @param string $stub
     *
     * @return MigrationPivotCommand
     */
    protected function replaceSchema(&$stub): MigrationPivotCommand
    {
        $tables = array_merge(
            $this->getSortedSingularTableNames(),
            $this->getSortedTableNames()
        );

        $stub = str_replace(
            ['{{columnOne}}', '{{columnTwo}}', '{{tableOne}}', '{{tableTwo}}'],
            $tables,
            $stub
        );

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceClass($stub, $name): string
    {
        $stub = str_replace('{{class}}', $name, $stub);

        return $stub;
    }

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    protected function getPivotTableName(): string
    {
        return implode('_', $this->getSortedSingularTableNames());
    }

    /**
     * Sort the two tables in alphabetical order.
     *
     * @return array
     */
    protected function getSortedTableNames(): array
    {
        $tables = $this->getTableNamesFromInput();

        sort($tables);

        return $tables;
    }

    /**
     * Sort the two tables in alphabetical order, in singular form.
     *
     * @return array
     */
    protected function getSortedSingularTableNames(): array
    {
        $tables = array_map('Str::singular', $this->getTableNamesFromInput());

        sort($tables);

        return $tables;
    }

    /**
     * Get the table names from input.
     *
     * @return array
     */
    protected function getTableNamesFromInput(): array
    {
        return [
            strtolower($this->argument('tableOne')),
            strtolower($this->argument('tableTwo')),
        ];
    }

    /**
     * @return bool
     */
    protected function migrationAlreadyExist()
    {
        $migrationFiles = $this->files->glob(config('ha-generator.packageMigrationsFolder').'/*.php');

        foreach ($migrationFiles as $migrationFile) {
            $this->files->requireOnce($migrationFile);
        }

        return class_exists($this->getClassName());
    }
}
