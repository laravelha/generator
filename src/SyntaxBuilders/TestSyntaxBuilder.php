<?php

namespace Laravelha\Generator\SyntaxBuilders;

class TestSyntaxBuilder extends AbstractSintaxBuilder
{
    /**
     * Create the PHP syntax for the given schema.
     *
     * @param array $schema
     * @param bool $api
     * @return string
     */
    public function create(array $schema, bool $api): string
    {
        return $this->createSchema($schema, $api);
    }

    /**
     * Create the schema for the assert method.
     *
     * @param array $schema
     * @param bool $api
     * @return string
     */
    private function createSchema(array $schema, bool $api): string
    {
        $wrapperWithColumns = $this->createSchemaForColumn($schema, $api);

        $fields = $this->constructSchemaForRequired($schema, $api);

        return $this->insert($fields)->into($wrapperWithColumns, 'required');
    }

    /**
     * Create the schema for the assert method.
     *
     * @param array $schema
     * @param bool $api
     * @return string
     */
    private function createSchemaForColumn(array $schema, bool $api): string
    {
        $fields = $this->constructSchemaForColumn($schema);

        return $this->insert($fields)->into($this->getSchemaWrapper($api));
    }


    /**
     * Store the given template, to be inserted somewhere.
     *
     * @param  string $template
     * @return TestSyntaxBuilder
     */
    private function insert(string $template): TestSyntaxBuilder
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
    private function getSchemaWrapper(bool $api): string
    {
        $type = $api ? 'Api' : 'Web';

        return file_get_contents(__DIR__ . "/../stubs//tests/Feature/{$type}Test.stub");
    }

    /**
     * Construct the schema fields.
     *
     * @param  array $schema
     * @return string|array
     */
    private function constructSchemaForColumn(array $schema): string
    {
        if (!$schema) return '';

        $fields = array_map(function ($field) {
            return $this->addColumn($field);
        }, $schema);

        return implode("\n" . str_repeat(' ', 20), $this->removeEmpty($fields));
    }

    /**
     * Construct the schema fields.
     *
     * @param array $schema
     * @param bool $api
     * @return string|array
     */
    private function constructSchemaForRequired(array $schema, bool $api): string
    {
        if (!$schema) return '';

        $fields = array_map(function ($field) use ($api) {
            return $this->addRequired($field, $api);
        }, $schema);

        return implode("\n" . str_repeat(' ', 8), $this->removeEmpty($fields));
    }

    /**
     * Construct the syntax to add a rule.
     *
     * @param  array $field
     * @return string
     */
    private function addColumn(array $field): string
    {
        if($this->hasForeignConstraint($field))
            return '';

        return sprintf("'%s',", $field['name']);

    }

    /**
     * Construct the syntax to add a required.
     *
     * @param array $field
     * @param bool $api
     * @return string
     */
    private function addRequired(array $field, bool $api): string
    {
        if ($this->hasForeignConstraint($field))
            return '';

        if (array_key_exists('nullable', $field['options']))
            return '';

        $assert = $api ? 'assertJsonValidationErrors' : 'assertSessionHasErrors';

        return sprintf("\$response->{$assert}('%s');", $field['name']);
    }
}
