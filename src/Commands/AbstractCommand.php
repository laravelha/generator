<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

abstract class AbstractCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var string
     */
    protected $modelName;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var string
     */
    protected $objectName;

    /**
     * @var string
     */
    protected $packageName;

    /**
     * @var string
     */
    protected $packageFolder;

    /**
     * @var string
     */
    protected $packagePath;

    /**
     * @var string
     */
    protected $packageRoute;

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
     * Set ModelName, TableName, RouteName and ObjectName by argument.
     *
     * @return void
     */
    protected function setNames(): void
    {
        $this->modelName = Str::studly($this->argument('name'));
        $this->namespace = 'App';
        $this->tableName = Str::plural(Str::snake($this->modelName));
        $this->routeName = Str::kebab(Str::plural($this->modelName));
        $this->objectName = Str::snake($this->modelName);

        if ($this->hasPackage()) {
            $this->packageName = Str::studly($this->argument('package'));
            $this->namespace = config('ha-generator.packagesNamespace').'\\'.$this->packageName;
            $this->packageFolder = Str::kebab($this->packageName);
            $this->packageRoute = Str::plural($this->packageFolder);
            $this->packagePath = config('ha-generator.packagesFolder').'/'.$this->packageFolder;
        }
    }

    /**
     * Build the directory for the command result if necessary.
     *
     * @param string $path
     */
    protected function makeDirectory($path): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }
    }

    /**
     * Replace the namespace in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceNamespace(string &$stub): AbstractCommand
    {
        $stub = str_replace('{{namespace}}', $this->namespace, $stub);

        return $this;
    }

    /**
     * Replace the namespace in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceTestNamespace(string &$stub): AbstractCommand
    {
        $namespace = $this->namespace === 'App' ? 'Tests' : $this->namespace.'\Tests';

        $stub = str_replace('{{namespace}}', $namespace, $stub);

        return $this;
    }

    /**
     * Replace the model namespace in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceModelNamespace(string &$stub): AbstractCommand
    {
        $namespace = $this->hasPackage() ? $this->namespace.'\\Models' : config('ha-generator.modelNamespace');

        $stub = str_replace('{{modelNamespace}}', $namespace, $stub);

        return $this;
    }

    /**
     * Replace the model name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceModelName(string &$stub): AbstractCommand
    {
        $stub = str_replace('{{modelName}}', $this->modelName, $stub);

        return $this;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceClassName(string &$stub): AbstractCommand
    {
        $className = ucwords(Str::camel($this->argument('name')));

        $stub = str_replace('{{class}}', $className, $stub);

        return $this;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceTableName(string &$stub): AbstractCommand
    {
        $stub = str_replace('{{tableName}}', $this->tableName, $stub);

        return $this;
    }

    /**
     * Replace the route name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replacePackageRouteName(string &$stub): AbstractCommand
    {
        $packageRouteName = $this->packageRoute ? '/'.$this->packageRoute : '';

        $stub = str_replace('{{packageRouteName}}', $packageRouteName, $stub);

        return $this;
    }

    /**
     * Replace the route name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceRouteName(string &$stub): AbstractCommand
    {
        $stub = str_replace('{{routeName}}', $this->routeName, $stub);

        return $this;
    }

    /**
     * Replace the object name in the stub.
     *
     * @param string $stub
     *
     * @return AbstractCommand
     */
    protected function replaceObjectName(string &$stub): AbstractCommand
    {
        $stub = str_replace('{{objectName}}', $this->objectName, $stub);

        return $this;
    }

    /**
     * Get the path to where we should store the command result.
     *
     * @param array $args
     *
     * @return string
     */
    abstract protected function getPath(...$args): string;

    /**
     * Compile the command result stub.
     *
     * @return string
     */
    abstract protected function compileStub(): string;

    /**
     * Log command.
     *
     * @return void
     */
    protected function writeLog(): void
    {
        if (!$this->option('no-log')) {
            $log = 'php artisan '.$this->name.' '.$this->argument('name');
            $log .= ($this->hasArgument('package') and $this->argument('package')) ? ' '.$this->argument('package') : '';
            $log .= ($this->hasArgument('view') and $this->argument('view')) ? ' '.$this->argument('view') : '';
            $log .= ($this->hasArgument('folder') and $this->argument('folder')) ? ' '.$this->argument('folder') : '';
            $log .= ($this->hasOption('api') and $this->option('api')) ? ' -a' : '';
            $log .= ($this->hasOption('schema') and $this->option('schema')) ? " -s '".$this->option('schema')."'" : '';
            $log .= ($this->hasOption('parent') and $this->option('parent')) ? " -p '".$this->option('parent')."'" : '';
            $log .= ($this->hasOption('ownarg') and $this->option('ownarg')) ? ' -o' : '';
            $log .= ($this->hasOption('argparent') and $this->option('argparent')) ? ' -ar' : '';

            $this->files->append(storage_path('logs/'.config('ha-generator.logFile').'-'.now()->format('Y-m-d').'.log'), $log.PHP_EOL);
        }
    }

    /**
     * @return bool
     */
    protected function hasPackage(): bool
    {
        return $this->hasArgument('package') and $this->argument('package');
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
}
