<?php

namespace Laravelha\Generator\Commands;

class BreadcrumbCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:breadcrumb
        {name : Model name (singular) for example User.}
        {view=index : View name.}
        {--no-log : No logging}
        {--p|parent= : Parent view name}
        {--o|ownarg : Parent view name}
        {--a|argparent : Parent parameter view name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert new resources breadcrumbs';

    /**
     * @var string
     */
    private $viewName;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->setNames();

        $this->viewName = strtolower($this->argument('view'));

        if (!$this->files->exists($path = $this->getPath())) {
            $this->error('Breadcrumb file not found');
            return;
        }

        $this->files->append($path, $this->compileStub());

        $this->line("<info>Inserted Breadcrumb:</info> {$this->viewName}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the breadcrumb.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        return base_path("routes/breadcrumbs.php");
    }

    /**
     * Compile the breadcrumb stub.
     *
     * @return string
     */
    protected function compileStub(): string
    {
        return $stub = $this->option('parent') ? $this->compileWithParentStub() : $this->compileWithoutParentStub();
    }

    /**
     * Compile the breadcrumb without parent stub.
     *
     * @return string
     */
    protected function compileWithParentStub(): string
    {
        $parentName = strtolower($this->option('parent'));

        $stub = $this->option('ownarg')
            ? PHP_EOL.'Breadcrumbs::for(\'{{routeName}}.'.$this->viewName.'\', function ($breadcrumbs, ${{objectName}}) {'.PHP_EOL
            : PHP_EOL.'Breadcrumbs::for(\'{{routeName}}.'.$this->viewName.'\', function ($breadcrumbs) {'.PHP_EOL;

        $stub .= $this->option('argparent')
            ? '   $breadcrumbs->parent(\'{{routeName}}.'.$parentName.'\', ${{objectName}});'.PHP_EOL
            : '   $breadcrumbs->parent(\'{{routeName}}.'.$parentName.'\');'.PHP_EOL;

        $stub .= $this->option('ownarg')
            ? '   $breadcrumbs->push(Lang::get(\'{{routeName}}.'.$this->viewName.'\', [\'{{objectName}}\' => ${{objectName}}->id]), route(\'{{routeName}}.'.$this->viewName.'\', ${{objectName}}->id));'.PHP_EOL.
            '});'.PHP_EOL
            : '   $breadcrumbs->push(Lang::get(\'{{routeName}}.'.$this->viewName.'\'), route(\'{{routeName}}.'.$this->viewName.'\'));'.PHP_EOL.
            '});'.PHP_EOL;

        $this->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Compile the breadcrumb without parent stub.
     *
     * @return string
     */
    protected function compileWithoutParentStub(): string
    {
        $stub = PHP_EOL.'Breadcrumbs::for(\'{{routeName}}.'.$this->viewName.'\', function ($breadcrumbs) {'.PHP_EOL.
            '   $breadcrumbs->push(Lang::get(\'{{routeName}}.'.$this->viewName.'\'), route(\'{{routeName}}.'.$this->viewName.'\'));'.PHP_EOL.
            '});'.PHP_EOL;

        $this->replaceRouteName($stub);

        return $stub;
    }
}
