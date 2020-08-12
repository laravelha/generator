<?php

namespace Laravelha\Generator\SyntaxBuilders;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

class ViewSyntaxBuilder extends AbstractSintaxBuilder
{
    /**
     * A template to be inserted.
     *
     * @var string
     */
    private $viewName;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param string $viewName
     * @param  Filesystem  $files
     */
    public function __construct(string $viewName, Filesystem $files)
    {
        parent::__construct();

        $this->viewName = $viewName;
        $this->files = $files;
    }

    /**
     * Create the PHP syntax for the given schema.
     *
     * @param  array $schema
     * @return string
     */
    public function create(array $schema): string
    {
        return $this->createSchema($schema);
    }

    /**
     * Create the schema for the blade views.
     *
     * @param  array $schema
     * @return string
     */
    private function createSchema(array $schema): string
    {
        $fields = $this->constructSchema($schema);

        return $this->insert($fields)->into($this->getSchemaWrapper());
    }

    /**
     * Store the given template, to be inserted somewhere.
     *
     * @param  string $template
     * @return ViewSyntaxBuilder
     */
    private function insert(string $template): ViewSyntaxBuilder
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the stored template, and insert into the given wrapper.
     *
     * @param  string $wrapper
     * @param  string $placeholder
     * @return string
     */
    private function into(string $wrapper, string $placeholder = 'column'): string
    {
        return str_replace('{{' . $placeholder . '}}', $this->template, $wrapper);
    }

    /**
     * Get the wrapper template.
     *
     * @return string
     */
    private function getSchemaWrapper(): string
    {
        return file_get_contents($this->resolveStubPath("/resources/views/{$this->viewName}.blade.stub"));
    }

    /**
     * Construct the schema fields.
     *
     * @param  array $schema
     * @return string|array
     */
    private function constructSchema(array $schema)
    {
        if (!$schema) return '';

        $fields = array_map(function ($field) {
            return $this->addColumn($field);
        }, $schema);

        return implode("\n" . str_repeat(' ', 8), $this->removeEmpty($fields));
    }

    /**
     * Construct the syntax to add a column.
     *
     * @param  array  $field
     * @return string
     * @throws FileNotFoundException
     */
    private function addColumn(array $field): string
    {
        if($this->hasForeignConstraint($field))
            return '';

        return str_replace('{{column}}', $field['name'], $this->getElementStub($field));
    }

    /**
     * @param  array  $field
     * @return string
     * @throws FileNotFoundException
     */
    private function getElementStub(array $field): string
    {
        $element = 'text';

        if (in_array($field['type'], $this->stringTypes)) {
            $element = 'text';

            if ($field['type'] == 'text' || ($field['arguments'] && $field['arguments'][0] > 100))
                $element = 'textarea';
        }

        if (in_array($field['type'], $this->integerTypes))
            $element = 'number';

        if (in_array($field['type'], $this->floatTypes))
            $element = 'decimal';

        if (in_array($field['type'], $this->dateTypes))
            $element = 'date';

        $strSub = $this->files->get($this->resolveStubPath("/resources/views/elements/{$element}.blade.stub"));

        $strSub = str_replace('{{required}}', !array_key_exists('nullable', $field['options']) ? 'required' : '', $strSub);

        if (in_array($field['type'], $this->stringTypes)) {
            if ($field['arguments'] && $field['arguments'][0])
                $strSub = str_replace('{{maxlength}}', $field['arguments'][0], $strSub);
            else
                $strSub = str_replace('{{maxlength}}', '255', $strSub);
        }

        if (in_array($field['type'], $this->integerTypes)) {
            if ($field['arguments'] && $field['arguments'][0]) {
                $strSub = str_replace('{{min}}', 'min="0"', $strSub);
                $strSub = str_replace('{{max}}', 'max="' . str_repeat('9', $field['arguments'][0]) . '"', $strSub);
            } else {
                $strSub = str_replace('{{min}}', '', $strSub);
                $strSub = str_replace('{{max}}', '', $strSub);
            }
        }

        if (in_array($field['type'], $this->floatTypes)) {
            if ($field['arguments'] && $field['arguments'][0] && $field['arguments'][1]) {
                $strSub = str_replace('{{digits}}', str_repeat('0', $field['arguments'][0] - $field['arguments'][1]), $strSub);
                $strSub = str_replace('{{precision}}', str_repeat('0', $field['arguments'][1]), $strSub);
            } else {
                $strSub = str_replace('{{digits}}', str_repeat('0', 8), $strSub);
                $strSub = str_replace('{{precision}}', str_repeat('0', 2), $strSub);
            }
        }

        return $strSub;
    }
}
