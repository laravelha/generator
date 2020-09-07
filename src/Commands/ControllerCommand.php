<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\ControllerApiSyntaxBuilder;

class ControllerCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:controller
        {name : Model name (singular) for example User}
        {package? : Package name (Optional)}
        {--no-log : No logging}
        {--a|api : The application is an API?}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller';

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
            $this->error('Controller already exists!');

            return;
        }

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Controller:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the controller.
     *
     * @param array $args
     *
     * @return string
     */
    protected function getPath(...$args): string
    {
        if ($this->hasPackage()) {
            $this->makeDirectory($path = $this->packagePath."/src/Http/Controllers/{$this->modelName}Controller.php");

            return $path;
        }

        return app_path("Http/Controllers/{$this->modelName}Controller.php");
    }

    /**
     * Compile the controller stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileStub(): string
    {
        return $stub = $this->option('api') ? $this->compileApiControllerStub() : $this->compileWebControllerStub();
    }

    /**
     * Compile the controller api stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileApiControllerStub(): string
    {
        $stub = $this->files->get($this->resolveStubPath('/app/Http/Controllers/ApiController.stub'));

        $this->apiResources();

        $this
            ->replaceSchema($stub)
            ->replaceNamespace($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub)
            ->replaceTableName($stub)
            ->replacePackageRouteName($stub)
            ->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Compile the controller web stub.
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileWebControllerStub(): string
    {
        $stub = $this->files->get($this->resolveStubPath('/app/Http/Controllers/WebController.stub'));

        $this
            ->replaceNamespace($stub)
            ->replaceModelNamespace($stub)
            ->replaceModelName($stub)
            ->replaceTableName($stub)
            ->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Run api resources command.
     *
     * @return void
     */
    private function apiResources(): void
    {
        try {
            $params = ['name' => $this->modelName, '--no-log' => $this->option('no-log')];
            $params += $this->hasPackage() ? ['package' => $this->argument('package')] : [];

            $this->call('ha-generator:resources', $params);
        } catch (\Exception $exception) {
            $this->warn("Não foi possível criar recursos para {$this->modelName}");
        }
    }

    /**
     * Replace the schema for the stub.
     *
     * @param string $stub
     *
     * @return ControllerCommand
     */
    protected function replaceSchema(string &$stub): ControllerCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser())->parse($schema);
        }

        $stub = (new ControllerApiSyntaxBuilder())->create($schema);

        return $this;
    }
}
