<?php

namespace Laravelha\Generator\Parsers;

use Illuminate\Support\Str;

class SchemaParser
{
    /**
     * The parsed schema.
     *
     * @var array
     */
    private $schema = [];

    /**
     * Parse the command line migration schema.
     * Ex: name:string, age:integer:nullable.
     *
     * @param string $schema
     *
     * @return array
     */
    public function parse(string $schema): array
    {
        $fields = $this->splitIntoFields($schema);

        foreach ($fields as $field) {
            $segments = $this->parseSegments($field);

            if ($this->fieldNeedsForeignConstraint($segments)) {
                unset($segments['options']['foreign']);

                // If the user wants a foreign constraint, then
                // we'll first add the regular field.
                $this->addField($segments);

                // And then add another field for the constraint.
                $this->addForeignConstraint($segments);

                continue;
            }

            $this->addField($segments);
        }

        return $this->schema;
    }

    /**
     * Add a field to the schema array.
     *
     * @param array $field
     *
     * @return SchemaParser
     */
    private function addField(array $field): SchemaParser
    {
        $this->schema[] = $field;

        return $this;
    }

    /**
     * Get an array of fields from the given schema.
     *
     * @param string $schema
     *
     * @return array
     */
    private function splitIntoFields(string $schema): array
    {
        return preg_split('/,\s?(?![^()]*\))/', $schema);
    }

    /**
     * Get the segments of the schema field.
     *
     * @param string $field
     *
     * @return array
     */
    private function parseSegments(string $field): array
    {
        $segments = explode(':', $field);

        $name = array_shift($segments);
        $type = array_shift($segments);

        $arguments = [];
        $options = $this->parseOptions($segments);

        // Do we have arguments being used here?
        // Like: string(100)
        if (preg_match('/(.+?)\(([^)]+)\)/', $type, $matches)) {
            $type = $matches[1];
            $arguments = explode(',', $matches[2]);
        }

        if (!in_array($type, config('ha-generator.bluePrintTypes'))) {
            throw new \InvalidArgumentException("Column Type: $type not available");
        }

        return compact('name', 'type', 'arguments', 'options');
    }

    /**
     * Parse any given options into something usable.
     *
     * @param array $options
     *
     * @return array
     */
    private function parseOptions(array $options): array
    {
        if (empty($options)) {
            return [];
        }

        foreach ($options as $option) {
            if (Str::contains($option, '(')) {
                preg_match('/([a-z]+)\(([^\)]+)\)/i', $option, $matches);

                $results[$matches[1]] = $matches[2];
            } else {
                $results[$option] = true;
            }
        }

        return $results;
    }

    /**
     * Add a foreign constraint field to the schema.
     *
     * @param array $segments
     */
    private function addForeignConstraint(array $segments): void
    {
        $string = sprintf(
            "%s:foreign:references('id'):on('%s')",
            $segments['name'],
            $this->getTableNameFromForeignKey($segments['name'])
        );

        $this->addField($this->parseSegments($string));
    }

    /**
     * Try to figure out the name of a table from a foreign key.
     * Ex: user_id => users.
     *
     * @param string $key
     *
     * @return string
     */
    private function getTableNameFromForeignKey(string $key): string
    {
        return Str::plural(str_replace('_id', '', $key));
    }

    /**
     * Determine if the user wants a foreign constraint for the field.
     *
     * @param array $segments
     *
     * @return bool
     */
    private function fieldNeedsForeignConstraint(array $segments): bool
    {
        return array_key_exists('foreign', $segments['options']);
    }
}
