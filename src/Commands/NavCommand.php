<?php

namespace Laravelha\Generator\Commands;

use DOMDocument;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class NavCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ha-generator:nav
        {name : Model name (singular) for example User}
        {--no-log : No logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new menu';

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

        if (!$this->files->exists($path = $this->getPath())) {
            $this->error('Nav not exists!');
            return;
        }

        $this->append($path, $this->compileStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Inserted Nav in:</info> {$filename}");

        $this->writeLog();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the path to where we should store the menu.
     *
     * @param array $args
     * @return string
     */
    protected function getPath(...$args): string
    {
        return resource_path("views/layouts/nav.blade.php");
    }

    /**
     * Append menu
     *
     * @param  string  $path
     * @param  string  $compileStub
     */
    protected function append(string $path, string $compileStub)
    {
        $menu = new DOMDocument;
        $menu->load($path);

        $item = new DOMDocument;
        $item->loadXML($compileStub);

        $menu->getElementsByTagName("ul")->item(0)->appendChild(
            $menu->importNode($item->getElementsByTagName("li")->item(0), true)
        );

        // LIBXML_NOXMLDECL remove xml header to Libxml >= 2.6.21
        $menu->save($path, LIBXML_NOXMLDECL);

        // If you don't have Libxml >= 2.6.21
        file_put_contents($path, preg_replace('/<\?xml[^>]+>\s+/', '', $this->indentContent(file_get_contents($path))));
    }

    /**
     * Compile the menu stub.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function compileStub(): string
    {
        $stub = $this->files->get($this->resolveStubPath("/resources/views/layouts/nav.stub"));

        $this->replaceRouteName($stub)
            ->replaceTableName($stub);

        return $stub;
    }

    /**
     * @param $content
     * @param  string  $tab
     * @return string
     * @See https://stackoverflow.com/questions/7838929/keeping-line-breaks-when-using-phps-domdocument-appendchild
     */
    protected function indentContent($content, $tab="\t")
    {
        $indent = 0;

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
