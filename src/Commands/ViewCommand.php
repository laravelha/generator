<?php

namespace Laravelha\Generator\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laravelha\Generator\Parsers\SchemaParser;
use Laravelha\Generator\SyntaxBuilders\ViewSyntaxBuilder;

class ViewCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:view
        {name : Model name (singular) for example User.}
        {view=index : View name [index, show, create, edit, delete]}
        {--no-log : No logging}
        {--s|schema= : Schema options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new resources views';

    /**
     * @var string
     */
    private $viewName;

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

        $this->viewName = strtolower($this->argument('view'));

        if ($this->files->exists($path = $this->getPath())) {
            $this->error('View already exists!');
            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created View:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the view.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        return resource_path("views/{$this->tableName}/{$this->viewName}.blade.php");
    }

    /**
     * Compile the view stub.
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(): string
    {
        $stub = $this->files->get(self::STUB_DIR . "/resources/views/{$this->viewName}.blade.stub");

        if($this->option('schema'))
            $this->replaceSchema($stub);

        $this->replaceTableName($stub)
            ->replaceRouteName($stub)
            ->replaceObjectName($stub);

        return $stub;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string  $stub
     * @return ViewCommand
     */
    protected function replaceSchema(string &$stub): ViewCommand
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
        }

        $stub = (new ViewSyntaxBuilder($this->viewName, $this->files))->create($schema);

        return $this;
    }

    /**
     * @param $content
     * @param  string  $tab
     * @return string
     * @See https://stackoverflow.com/questions/7838929/keeping-line-breaks-when-using-phps-domdocument-appendchild
     */
    protected function indentContent($content, $tab="\t")
    {
        $indent=0;

        // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
        $content = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $content);

        // now indent the tags
        $token = strtok($content, "\n");
        $result = ''; // holds formatted version as it is built
        $pad = 0; // initial indent
        $matches = array(); // returns from preg_matches()

        // scan each line and adjust indent based on opening/closing tags
        while ($token !== false)
        {
            $token = trim($token);
            // test for the various tag states

            // 1. open and closing tags on same line - no change
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) $indent=0;
            // 2. closing tag - outdent now
            elseif (preg_match('/^<\/\w/', $token, $matches))
            {
                $pad--;
                if($indent>0) $indent=0;
            }
            // 3. opening tag - don't pad this one, only subsequent tags
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) $indent=1;
            // 4. no indentation needed
            else $indent = 0;

            // pad the line with the required number of leading spaces
            $line = str_pad($token, strlen($token)+$pad, $tab, STR_PAD_LEFT);
            $result .= $line."\n"; // add to the cumulative result, with linefeed
            $token = strtok("\n"); // get the next token
            $pad += $indent; // update the pad size for subsequent lines
        }

        return $result;
    }
}
