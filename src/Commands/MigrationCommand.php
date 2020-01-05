<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Laravelha\Generator\GeneratorException;
use Laravelha\Generator\Parsers\MigrationNameParser;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\MigrationSyntaxBuilder;

class MigrationCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:migration
        {name : Class (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration class and apply schema at the same time';

    /**
     * Meta information for the requested migration.
     *
     * @var array
     */
    protected $meta;

    /**
     * @var string
     */
    const STUB_DIR = __DIR__ . "/../stubs";

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     * @throws GeneratorException
     */
    public function handle(): void
    {
        $this->setNames();

        $this->meta = (new MigrationNameParser)->parse($this->argument('name'));

        $name = $this->argument('name');

        if ($this->files->exists($path = $this->getPath($name))) {
            $this->error('Migration already exists!');
            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Migration:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            return $this->packagePath . '/' . config('ha-generator.packageMigrationsFolder') . '/' . date('Y_m_d_His') . '_' . $args[0] . '.php';
        }

        return base_path() . '/database/migrations/' . date('Y_m_d_His') . '_' . $args[0] . '.php';

    }

    /**
     * Compile the migration stub.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(): string
    {
        $stub = $this->files->get(self::STUB_DIR . "/database/migrations/migration.stub");

        $this->replaceClassName($stub)
            ->replaceSchema($stub)
            ->replaceTableName($stub);

        return $stub;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param  string $stub
     * @return AbstractCommand
     */
    protected function replaceTableName(string &$stub): AbstractCommand
    {
        $table = $this->meta['table'];

        $stub = str_replace('{{table}}', $table, $stub);

        return $this;
    }


    /**
     * Replace the schema for the stub.
     *
     * @param  string  $stub
     * @return MigrationCommand
     * @throws GeneratorException
     */
    protected function replaceSchema(string &$stub): MigrationCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
        }

        $schema = (new MigrationSyntaxBuilder)->create($schema, $this->meta);

        $stub = str_replace(['{{schema_up}}', '{{schema_down}}'], $schema, $stub);

        return $this;
    }
}
