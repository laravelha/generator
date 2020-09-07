<?php

namespace Laravelha\Generator\Parsers;

class MigrationNameParser
{
    /**
     * Parse the migration name into something we can use.
     *
     * @param string $name
     *
     * @return array
     */
    public function parse(string $name): array
    {
        $segments = array_reverse(explode('_', $name));

        if ($segments[0] == 'table') {
            array_shift($segments);
        }

        return [
            'action' => $this->getAction($segments),
            'table'  => $this->getTableName($segments),
        ];
    }

    /**
     * Calculate the table name.
     *
     * @param array $segments
     *
     * @return string
     */
    private function getTableName(array $segments): string
    {
        $tableName = [];

        foreach ($segments as $segment) {
            if ($this->isConnectingWord($segment)) {
                break;
            }

            $tableName[] = $segment;
        }

        return implode('_', array_reverse($tableName));
    }

    /**
     * Determine the user's desired action for the migration.
     *
     * @param array $segments
     *
     * @return string
     */
    private function getAction(array &$segments): string
    {
        return $this->normalizeActionName(array_pop($segments));
    }

    /**
     * Normalize the user's chosen action to name to
     * something that we recognize.
     *
     * @param string $action
     *
     * @return string
     */
    private function normalizeActionName($action): string
    {
        switch ($action) {
            case 'create':
            case 'make':
                return 'create';
            case 'delete':
            case 'destroy':
            case 'drop':
                return 'remove';
            case 'add':
            case 'append':
            case 'update':
            case 'insert':
                return 'add';
            default:
                return $action;
        }
    }

    /**
     * Determine if the current segment is a connecting word.
     *
     * @param string $segment
     *
     * @return bool
     */
    private function isConnectingWord(string $segment): bool
    {
        $connectors = ['to', 'from', 'and', 'with', 'for', 'in', 'of', 'on'];

        return in_array($segment, $connectors);
    }
}
