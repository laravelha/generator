<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ha-generator:crud
        {name : Class (singular) for example User}
        {package? : Package name (Optional)}
        {--log-detailed : Show log for each command on this}
        {--no-log : No logging}
        {--y|yes : Skip confirmation}
        {--a|api : The application is an API?}
        {--d|datatables : Use to datatables}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

    /**
     * Logging.
     *
     * @var bool
     */
    protected $noLogDetailed;

    /**
     * @var string
     */
    const STUB_DIR = __DIR__.'/../stubs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!$this->option('schema')) {
            $this->error('The "schema" argument is required!');

            return;
        }

        $this->noLogDetailed = !($this->option('log-detailed') and !$this->option('no-log'));

        $name = $this->argument('name');

        $this->option('yes') ? $this->withoutConfirmation($name) : $this->withConfirmation($name);

        if (!$this->option('log-detailed')) {
            $this->writeLog();
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function migration(string $name): void
    {
        $namePluralLower = Str::plural(Str::snake($name));

        try {
            $params = ['name' => "create_{$namePluralLower}_table", '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];

            $this->call('ha-generator:migration', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create migration 'create_{$namePluralLower}_table'. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function model(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema'), '--api' => $this->option('api')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];
            $params += $this->option('datatables') ? ['--datatables' => $this->option('datatables')] : [];

            $this->call('ha-generator:model', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create model {$name}. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function factory(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];

            $this->call('ha-generator:factory', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create factory {$name}Factory. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function requests(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];

            $this->call('ha-generator:request', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create requests {$name}Request. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function controller(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema'), '--api' => $this->option('api')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];
            $params += $this->option('datatables') ? ['--datatables' => $this->option('datatables')] : [];

            $this->call('ha-generator:controller', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create controller {$name}. {$exception->getMessage()}");
            $this->warn($exception->getMessage());
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function routes(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--api' => $this->option('api')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];
            $params += $this->option('datatables') ? ['--datatables' => $this->option('datatables')] : [];

            $this->call('ha-generator:route', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create routes to {$name}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function tests(string $name): void
    {
        try {
            $params = ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema'), '--api' => $this->option('api')];
            $params += $this->hasArgument('package') ? ['package' => $this->argument('package')] : [];

            $this->call('ha-generator:tests', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create tests to {$name}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function views(string $name): void
    {
        if ($this->option('api')) {
            return;
        }

        $this->lang($name);
        $this->nav($name);

        // create view index
        $this->breadcrumbs($name);
        $this->viewFile($name);

        // create view create
        $this->breadcrumbs($name, 'create', 'index');
        $this->viewFile($name, 'create', $this->option('schema'));

        // create view show
        $this->breadcrumbs($name, 'show', 'index');
        $this->viewFile($name, 'show');

        // create view edit
        $this->breadcrumbs($name, 'edit', 'show');
        $this->viewFile($name, 'edit', $this->option('schema'));

        // create view delete
        $this->breadcrumbs($name, 'delete', 'show');
        $this->viewFile($name, 'delete');

        $this->info("Views '".Str::plural(strtolower($name))."' created");
        $this->comment('Run "npm install && npm run dev" to complete.');
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function viewsWithConfirmation(string $name): void
    {
        if ($this->option('api')) {
            return;
        }

        if ($this->confirm('Do you wish to create the lang?', true)) {
            $this->lang($name);
        }

        if ($this->confirm('Do you wish to create the navigation?', true)) {
            $this->nav($name);
        }

        if ($this->confirm('Do you wish to create the view index?', true)) {
            $this->breadcrumbs($name);
            $this->viewFile($name);
        }

        if ($this->confirm('Do you wish to create the view create?', true)) {
            $this->breadcrumbs($name, 'create', 'index');
            $this->viewFile($name, 'create', $this->option('schema'));
        }

        if ($this->confirm('Do you wish to create the view show?', true)) {
            $this->breadcrumbs($name, 'show', 'index');
            $this->viewFile($name, 'show');
        }

        if ($this->confirm('Do you wish to create the view edit?', true)) {
            $this->breadcrumbs($name, 'edit', 'show');
            $this->viewFile($name, 'edit', $this->option('schema'));
        }

        if ($this->confirm('Do you wish to create the view delete?', true)) {
            $this->breadcrumbs($name, 'delete', 'show');
            $this->viewFile($name, 'delete');
        }

        $this->info("Views '".Str::plural(strtolower($name))."' created");
        $this->comment('Run "npm install && npm run dev" to complete.');
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function lang(string $name): void
    {
        try {
            $this->call('ha-generator:lang', ['name' => $name, '--no-log' => $this->noLogDetailed, '--schema' => $this->option('schema')]);
        } catch (\Exception $exception) {
            $this->warn("Unable to create lang to {$name}. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function nav(string $name): void
    {
        try {
            $this->call('ha-generator:nav', ['name' => $name, '--no-log' => $this->noLogDetailed]);
        } catch (\Exception $exception) {
            $this->warn("Unable to create nav to {$name}");
        }
    }

    /**
     * @param string      $name
     * @param string|null $view
     * @param string|null $parent
     *
     * @return void
     */
    protected function breadcrumbs(string $name, string $view = null, string $parent = null): void
    {
        $params = ['name' => $name, '--no-log' => $this->noLogDetailed];

        if ($view) {
            $params['view'] = $view;
        }

        if ($parent) {
            $params['--parent'] = $parent;
        }

        if ($parent != 'index') {
            $params['--argparent'] = true;
        }

        if ($view != 'create') {
            $params['--ownarg'] = true;
        }

        try {
            $this->call('ha-generator:breadcrumb', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create breadcrumb to {$name} {$view}");
        }
    }

    /**
     * @param string      $name
     * @param string|null $view
     * @param string      $schema
     *
     * @return void
     */
    private function viewFile(string $name, string $view = null, string $schema = null): void
    {
        $params = ['name' => $name, '--no-log' => $this->noLogDetailed];
        $params += $this->option('datatables') ? ['--datatables' => $this->option('datatables')] : [];

        if ($view) {
            $params['view'] = $view;
        }

        if ($schema) {
            $params['--schema'] = $schema;
        }

        try {
            $this->call('ha-generator:view', $params);
        } catch (\Exception $exception) {
            $this->warn("Unable to create view to {$name} {$view}. {$exception->getMessage()}");
        }
    }

    /**
     * Log command.
     *
     * @return void
     */
    private function writeLog(): void
    {
        if (!$this->option('no-log')) {
            $log = 'php artisan '.$this->name.' '.$this->argument('name');
            $log .= ($this->hasOption('api') and $this->option('api')) ? ' -a' : '';
            $log .= ($this->hasOption('schema') and $this->option('schema')) ? " -s '".$this->option('schema')."'" : '';

            File::append(storage_path('logs/'.config('ha-generator.logFile').'-'.now()->format('Y-m-d').'.log'), $log.PHP_EOL);
        }
    }

    /**
     * @param string $name
     */
    private function withConfirmation(string $name): void
    {
        if ($this->confirm('Do you wish to create the migration?', true)) {
            $this->migration($name);
        }

        if ($this->confirm('Do you wish to create the model?', true)) {
            $this->model($name);
        }

        if ($this->confirm('Do you wish to create the factory?', true)) {
            $this->factory($name);
        }

        if ($this->confirm('Do you wish to create the requests?', true)) {
            $this->requests($name);
        }

        if ($this->confirm('Do you wish to create the controller?', true)) {
            $this->controller($name);
        }

        if ($this->confirm('Do you wish to create the routes?', true)) {
            $this->routes($name);
        }

        if ($this->confirm('Do you wish to create the tests?', true)) {
            $this->tests($name);
        }

        if (!$this->option('api') and $this->confirm('Do you wish to create the views?', true)) {
            $this->viewsWithConfirmation($name);
        }
    }

    /**
     * @param string $name
     */
    private function withoutConfirmation(string $name): void
    {
        $this->migration($name);
        $this->model($name);
        $this->factory($name);
        $this->requests($name);
        $this->controller($name);
        $this->routes($name);
        $this->tests($name);
        $this->views($name);
    }
}
