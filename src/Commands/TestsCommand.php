<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\TestSyntaxBuilder;

class TestsCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:tests
        {name : Table name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--a|api : The application is an API?}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test and apply schema at the same time';

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

        if ($directoryExists = $this->files->exists($path = $this->getPath())) {
            $this->error('Test already exists!');

            return;
        }

        if (!$directoryExists) {
            $this->makeDirectory($path);
        }

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Test:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the test.
     *
     * @param array $args
     *
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            return $this->packagePath."/tests/Feature/{$this->modelName}Test.php";
        }

        return base_path("tests/Feature/{$this->modelName}Test.php");
    }

    /**
     * Compile the requests stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileStub(): string
    {
        $stub = $this->option('api')
            ? $this->files->get($this->resolveStubPath('/tests/Feature/ApiTest.stub'))
            : $this->files->get($this->resolveStubPath('/tests/Feature/WebTest.stub'));

        $this->replaceSchema($stub)
            ->replacePackageRouteName($stub)
            ->replaceTestNamespace($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub)
            ->replaceTableName($stub)
            ->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param string $stub
     *
     * @return TestsCommand
     */
    protected function replaceSchema(string &$stub): TestsCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser())->parse($schema);
        }

        $stub = (new TestSyntaxBuilder())->create($schema, $this->option('api'));

        return $this;
    }
}
