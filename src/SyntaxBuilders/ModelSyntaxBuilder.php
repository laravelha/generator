<?php

namespace Laravelha\Generator\SyntaxBuilders;

use Illuminate\Support\Str;

class ModelSyntaxBuilder extends AbstractSintaxBuilder
{
    /**
     * Create the PHP syntax for the given schema.
     *
     * @param array $schema
     *
     * @return array
     */
    public function create(array $schema): array
    {
        $column = $this->createSchemaForColumn($schema);
        $foreign = $this->createSchemaForForeign($schema);
        $searchable = $this->createSchemaForSearchable($schema);

        return compact('column', 'foreign', 'searchable');
    }

    /**
     * Create the schema for the columns in getColumns.
     *
     * @param array $schema
     *
     * @return string
     */
    private function createSchemaForColumn(array $schema): string
    {
        $fields = $this->constructSchema($schema);

        return $this->insert($fields)->into($this->getSchemaWrapper());
    }

    /**
     * Create the schema for the foreign methods.
     *
     * @param array $schema
     *
     * @return string
     */
    private function createSchemaForForeign(array $schema): string
    {
        return $this->constructSchema($schema, 'addForeign');
    }

    /**
     * Create the schema for the api searchable method.
     *
     * @param array $schema
     *
     * @return string
     */
    private function createSchemaForSearchable(array $schema): string
    {
        $fields = $this->constructSchema($schema, 'addSearchable');

        return $this->insert($fields)->into($this->getSchemaWrapper('Searchables'), 'searchable');
    }

    /**
     * Store the given template, to be inserted somewhere.
     *
     * @param string $template
     *
     * @return ModelSyntaxBuilder
     */
    private function insert(string $template): ModelSyntaxBuilder
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the stored template, and insert into the given wrapper.
     *
     * @param string $wrapper
     * @param string $placeholder
     *
     * @return string
     */
    private function into(string $wrapper, string $placeholder = 'column'): string
    {
        return str_replace('{{'.$placeholder.'}}', $this->template, $wrapper);
    }

    /**
     * Get the wrapper template.
     *
     * @param string $type
     *
     * @return string
     */
    private function getSchemaWrapper(string $type = 'Column'): string
    {
        return file_get_contents($this->resolveStubPath("/app/{$type}Model.stub"));
    }

    /**
     * Construct the schema fields.
     *
     * @param array  $schema
     * @param string $method
     *
     * @return string|array
     */
    private function constructSchema(array $schema, string $method = 'addColumn')
    {
        if (!$schema) {
            return '';
        }

        $fields = array_map(function ($field) use ($method) {
            return $this->$method($field);
        }, $schema);

        return implode("\n".str_repeat(' ', 12), $this->removeEmpty($fields));
    }

    /**
     * Construct the syntax to add a column.
     *
     * @param array $field
     *
     * @return string
     */
    private function addColumn(array $field): string
    {
        if (array_key_exists('nullable', $field['options'])) {
            return '';
        }

        if ($this->hasForeignConstraint($field)) {
            return '';
        }

        return sprintf("['data' => '%s'],", $field['name']);
    }

    /**
     * Construct the syntax to add a foreign.
     *
     * @param array $field
     *
     * @return string
     */
    private function addForeign(array $field): string
    {
        if (!$this->hasForeignConstraint($field)) {
            return '';
        }

        $objectForeign = Str::singular(str_replace("'", '', $field['options']['on']));

        return str_replace(['{{objectForeigntName}}', '{{ModelForeigntName}}'], [$objectForeign, ucwords($objectForeign)], $this->getSchemaWrapper('Foreign'));
    }

    /**
     * Construct the syntax to add a searchable.
     *
     * @param array $field
     *
     * @return string
     */
    private function addSearchable(array $field): string
    {
        if (array_key_exists('nullable', $field['options'])) {
            return '';
        }

        if ($this->hasForeignConstraint($field)) {
            return '';
        }

        return sprintf("'%s' => 'like',", $field['name']);
    }

    /**
     * @param array $schema
     *
     * @return array
     */
    private function getRequiredFields(array $schema): array
    {
        $fields = [];
        foreach ($schema as $field) {
            if (!array_key_exists('nullable', $field['options'])) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
