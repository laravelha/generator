<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class PackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ha-generator:package
        {name : Package name (singular) for example Blog}
        {--no-log : No logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create package';

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
     * Package namespace: defined on config file.
     *
     * @var string
     */
    private $packageNamespace;

    /**
     * Package name: singular studly.
     *
     * @var string
     */
    protected $packageName;

    /**
     * Folder name: package name kebab.
     *
     * @var string
     */
    protected $folderName;

    /**
     * Route name: plural package name kebab.
     *
     * @var string
     */
    protected $routeName;

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
        $this->setNames();

        $this->makeComposer();
        $this->makeGitIgnore();
        $this->makeReadme();

        $this->makePHPUnit();
        $this->makeTestCase();

        $this->makeConfig();

        $this->makePackageServiceProvider();

        $this->makeRoutesWeb();
        $this->makeRoutesApi();
        $this->makeRouteServiceProvider();

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Setup names.
     *
     * @return void
     */
    protected function setNames(): void
    {
        $this->packageName = Str::studly($this->argument('name'));
        $this->packageNamespace = config('ha-generator.packagesNamespace').'\\'.$this->packageName;
        $this->folderName = Str::kebab($this->packageName);
        $this->routeName = Str::plural($this->folderName);
    }

    /**
     * Create composer.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeComposer(): void
    {
        if ($this->files->exists($path = $this->getPath().'/composer.json')) {
            $this->error('Composer already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->packageNamespace = str_replace('\\', '\\\\', $this->packageNamespace);

        $this->files->put($path, $this->compileStub('composer'));

        $this->packageNamespace = str_replace('\\\\', '\\', $this->packageNamespace);

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Composer:</info> {$filename}");
    }

    /**
     * Create composer.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeGitIgnore(): void
    {
        if ($this->files->exists($path = $this->getPath().'/.gitignore')) {
            $this->error('GitIgnore already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('gitignore'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created GitIgnore:</info> {$filename}");
    }

    /**
     * Create composer.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makePHPUnit(): void
    {
        if ($this->files->exists($path = $this->getPath().'/phpunit.xml')) {
            $this->error('PHPUnit already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('phpunit'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created PHPUnit:</info> {$filename}");
    }

    /**
     * Create composer.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeTestCase(): void
    {
        if ($this->files->exists($path = $this->getPath().'/tests/TestCase.php')) {
            $this->error('TestCase already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('/tests/TestCase'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created TestCase:</info> {$filename}");
    }

    /**
     * Create readme.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeReadme(): void
    {
        if ($this->files->exists($path = $this->getPath().'/README.md')) {
            $this->error('Readme already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('README'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Readme:</info> {$filename}");
    }

    /**
     * Create config.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeConfig(): void
    {
        if ($this->files->exists($path = $this->getPath().'/'.config('ha-generator.packageConfigsFolder').'/'.$this->folderName.'.php')) {
            $this->error('Config already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('src/Config/config'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Config:</info> {$filename}");
    }

    /**
     * Create package service provider.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makePackageServiceProvider(): void
    {
        if ($this->files->exists($path = $this->getPath().'/src/Providers/'.$this->packageName.'ServiceProvider.php')) {
            $this->error('ServiceProvider already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created ServiceProvider:</info> {$filename}");
    }

    /**
     * Create routes web.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeRoutesWeb(): void
    {
        if ($this->files->exists($path = $this->getPath().'/'.config('ha-generator.packageRoutesFolder').'/web.php')) {
            $this->error('Route web already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('src/Routes/web'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Web Routes:</info> {$filename}");
    }

    /**
     * Create routes api.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeRoutesApi(): void
    {
        if ($this->files->exists($path = $this->getPath().'/'.config('ha-generator.packageRoutesFolder').'/api.php')) {
            $this->error('Route web already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('src/Routes/api'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Api Routes:</info> {$filename}");
    }

    /**
     * Create route service provider.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function makeRouteServiceProvider(): void
    {
        if ($this->files->exists($path = $this->getPath().'/src/Providers/RouteServiceProvider.php')) {
            $this->error('RouteServiceProvider already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub('src/Providers/RouteServiceProvider'));

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created RouteServiceProvider:</info> {$filename}");
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
     * Get package path.
     *
     * @return string
     */
    protected function getPath(): string
    {
        return config('ha-generator.packagesFolder').'/'.$this->folderName;
    }

    /**
     * Compile stub.
     *
     * @param string $type
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function compileStub($type = 'src/Providers/ServiceProvider')
    {
        $stub = $this->files->get($this->resolveStubPath("/packages/{$type}.stub"));

        $this
            ->replacePackageNamespace($stub)
            ->replacePackageVendor($stub)
            ->replacePackageFolders($stub)
            ->replacePackageName($stub)
            ->replaceFolderName($stub)
            ->replaceRouteName($stub);

        return $stub;
    }

    /**
     * Replace the package namespace in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replacePackageNamespace(string &$stub): PackageCommand
    {
        $stub = str_replace('{{packagesNamespace}}', $this->packageNamespace, $stub);

        return $this;
    }

    /**
     * Replace the package namespace in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replacePackageVendor(string &$stub): PackageCommand
    {
        $stub = str_replace('{{packagesVendor}}', config('ha-generator.packagesVendor'), $stub);

        return $this;
    }

    /**
     * Replace the package namespace in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replacePackageFolders(string &$stub): PackageCommand
    {
        $stub = str_replace('{{packageConfigsFolder}}', config('ha-generator.packageConfigsFolder'), $stub);
        $stub = str_replace('{{packageMigrationsFolder}}', config('ha-generator.packageMigrationsFolder'), $stub);
        $stub = str_replace('{{packageFactoriesFolder}}', config('ha-generator.packageFactoriesFolder'), $stub);
        $stub = str_replace('{{packageLangsFolder}}', config('ha-generator.packageLangsFolder'), $stub);
        $stub = str_replace('{{packageViewsFolder}}', config('ha-generator.packageViewsFolder'), $stub);
        $stub = str_replace('{{packageRoutesFolder}}', config('ha-generator.packageRoutesFolder'), $stub);

        return $this;
    }

    /**
     * Replace the package name in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replacePackageName(string &$stub): PackageCommand
    {
        $stub = str_replace('{{packageName}}', $this->packageName, $stub);

        return $this;
    }

    /**
     * Replace the package name in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replaceFolderName(string &$stub): PackageCommand
    {
        $stub = str_replace('{{folderName}}', $this->folderName, $stub);

        return $this;
    }

    /**
     * Replace the package name in the stub.
     *
     * @param string $stub
     *
     * @return PackageCommand
     */
    protected function replaceRouteName(string &$stub): PackageCommand
    {
        $stub = str_replace('{{routeName}}', $this->routeName, $stub);

        return $this;
    }

    /**
     * Log command.
     *
     * @return void
     */
    protected function writeLog(): void
    {
        if (!$this->option('no-log')) {
            $log = 'php artisan '.$this->name.' '.$this->argument('name');
            $this->files->append(storage_path('logs/'.config('ha-generator.logFile').'-'.now()->format('Y-m-d').'.log'), $log.PHP_EOL);
        }
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
