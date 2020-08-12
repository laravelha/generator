<?php

namespace Laravelha\Generator\SyntaxBuilders;

class ControllerApiSyntaxBuilder extends AbstractSintaxBuilder
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
     * Create the schema.
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
     * @return ControllerApiSyntaxBuilder
     */
    private function insert(string $template): ControllerApiSyntaxBuilder
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
        return file_get_contents($this->resolveStubPath('/app/Http/Controllers/ApiController.stub'));
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

        return implode("\n" . str_repeat(' ', 5) . '*' . str_repeat(' ', 13), $this->removeEmpty($fields));
    }

    /**
     * Construct the syntax to add a column.
     *
     * @param  array $field
     * @return string
     */
    private function addColumn(array $field): string
    {
        if($this->hasForeignConstraint($field))
            return '';

        return sprintf("@OA\Property(property=\"%s\", type=\"%s\"),", $field['name'], $this->getDataType($field['type']));

    }

    /**
     * @param  string  $fieldType
     * @return string
     */
    private function getDataType(string $fieldType): string
    {
        if(in_array($fieldType, $this->integerTypes))
            return 'integer';

        if(in_array($fieldType,  $this->stringTypes))
            return 'string';

        if(in_array($fieldType,  $this->floatTypes))
            return 'number';

        if(in_array($fieldType, ['boolean']))
            return 'boolean';

        return 'string';
    }
}
