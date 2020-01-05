<?php

namespace Laravelha\Generator\SyntaxBuilders;

class RequestSyntaxBuilder extends AbstractSintaxBuilder
{
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
     * Create the schema for the array on rules method.
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
     * @return RequestSyntaxBuilder
     */
    private function insert(string $template): RequestSyntaxBuilder
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
    private function into(string $wrapper, string $placeholder = 'rule'): string
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
        return file_get_contents(self::STUB_DIR.'/app/Http/Requests/Request.stub');
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
            return $this->addRule($field);
        }, $schema);

        return implode("\n" . str_repeat(' ', 12), $this->removeEmpty($fields));
    }

    /**
     * Construct the syntax to add a rule.
     *
     * @param  array $field
     * @return string
     */
    private function addRule(array $field): string
    {
        if($this->hasForeignConstraint($field))
            return '';

        return sprintf("'%s' => '%s',", $field['name'], $this->getRule($field));

    }

    /**
     * @param  array  $field
     * @return string
     */
    private function getRule(array $field): string
    {
        $type = '';

        if (array_key_exists('nullable', $field['options']))
            $type .= 'nullable|';

        if (! array_key_exists('nullable', $field['options']))
            $type .= 'required|';

        if (in_array($field['type'], $this->stringTypes)) {
            $type .=  'string|';

            if ($field['arguments'])
                $type .= 'max:'.$field['arguments'][0].'|';
        }

        if (in_array($field['type'], $this->dateTypes))
            $type .=  'date_format:d/m/Y|';

        if (in_array($field['type'], $this->integerTypes))
            $type .=  'integer|';

        if (in_array($field['type'], $this->floatTypes))
            $type .=  'numeric|';

        return rtrim($type, '|');
    }
}
