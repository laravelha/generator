<?php

namespace Laravelha\Generator\SyntaxBuilders;

use Illuminate\Support\Str;

class FactorySintaxeBuilder extends AbstractSintaxBuilder
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
     * Create the schema for the data faker.
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
     * @return FactorySintaxeBuilder
     */
    private function insert(string $template): FactorySintaxeBuilder
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
        return file_get_contents(self::STUB_DIR.'/database/factories/factory.stub');
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
     * @param  array $field
     * @return string
     */
    private function addColumn(array $field): string
    {
        if ($this->hasForeignConstraint($field)) {
            $foreignName = Str::studly(str_replace('_id', '', $field['name']));
            return sprintf("'%s' => factory({{modelNamespace}}\\$foreignName::class),", $field['name']);
        }

        if (strpos($field['name'], '_id'))
            return '';

        return sprintf("'%s' => \$faker->%s,", $field['name'], $this->getFakerType($field));

    }

    /**
     * @param  array  $field
     * @return string
     */
    private function getFakerType(array $field): string
    {
        $type = 'text(';

        if (in_array($field['type'], $this->stringTypes))
            $type =  'text(';

        if (in_array($field['type'], $this->integerTypes))
            $type =  'randomNumber(';

        if (in_array($field['type'], $this->floatTypes)) {
            $type = 'randomFloat(';

            if ($field['arguments']) {
                $type .= $field['arguments'][1] . ', 0, ' . str_repeat('9', $field['arguments'][0] - $field['arguments'][1]) . '.' . str_repeat('9', $field['arguments'][1]);
            }
        }

        if (in_array($field['type'], $this->dateTypes))
            $type =  "date(";

        if (!in_array($field['type'], $this->floatTypes) && $field['arguments']) {
            $type .= implode(', ', $field['arguments']);
        }

        return $type.')';
    }
}
