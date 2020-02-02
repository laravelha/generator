<?php

namespace Laravelha\Generator\Commands;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FromExistingCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'ha-generator:existing:crud
                    {--log-detailed : Show log for each command on this}
                    {--no-log : No logging}
                    {--y|yes : Skip confirmation}
                    {--a|api : The application is an API?}
                    { --connection= : Connection name }
                    { --ignore= : Ignore tables }
                    { --table= : Table name }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate cruds from existing database';


    /**
     * Get a schema builder instance for a connection.
     *
     * @var Builder
     */
    protected $connection;

    /**
     * Get a schema manager instance for a connection.
     *
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * Mapper db type to blue print method
     *
     * @var array
     */
    protected $mapper = [
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'dateTime',
        'datetimetz' => 'dateTimeTz',
        'biginteger' => 'bigInteger',
        'bigint' => 'bigInteger',
        'tinyblob' => 'binary',
        'mediumblob' => 'binary',
        'blob' => 'binary',
        'longblob' => 'binary',
        'binary' => 'binary',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'bit' => 'boolean',
        'decimal' => 'decimal',
        'double' => 'double',
        'enum' => 'enum',
        'list' => 'enum',
        'float' => 'float',
        'int' => 'integer',
        'integer' => 'integer',
        'ipaddress' => 'ipAddress',
        'json' => 'json',
        'jsonb' => 'jsonb',
        'longtext' => 'longText',
        'macaddress' => 'macAddress',
        'mediuminteger' => 'mediumInteger',
        'mediumint' => 'mediumInteger',
        'mediumtext' => 'mediumText',
        'smallInteger' => 'smallInteger',
        'smallint' => 'smallInteger',
        'morphs' => 'morphs',
        'string' => 'string',
        'varchar' => 'string',
        'nvarchar' => 'string',
        'text' => 'text',
        'time' => 'time',
        'timetz' => 'timeTz',
        'tinyinteger' => 'tinyInteger',
        'tinyint' => 'tinyInteger',
        'timestamp' => 'timestamp',
        'timestamptz' => 'timestampTz',
        'unsignedbiginteger' => 'unsignedBigInteger',
        'unsignedbigint' => 'unsignedBigInteger',
        'unsignedInteger' => 'unsignedInteger',
        'unsignedint' => 'unsignedInteger',
        'unsignedmediuminteger' => 'unsignedMediumInteger',
        'unsignedmediumint' => 'unsignedMediumInteger',
        'unsignedsmallinteger' => 'unsignedSmallInteger',
        'unsignedsmallint' => 'unsignedSmallInteger',
        'unsignedtinyinteger' => 'unsignedTinyInteger',
        'uuid' => 'uuid',
    ];

    /**
     * Store tables created
     * @var array
     */
    protected $created = [];

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        $this->connection =  Schema::connection($this->hasOption( 'connection' ) ? $this->option( 'connection' ) : config('database.default'));

        $this->schemaManager = $this->connection->getConnection()->getDoctrineSchemaManager();

        if ($this->option('table'))
            $this->migrateTable($this->option('table'));

        if (! $this->option('table'))
            $this->migrateAll();

    }

    /**
     * @return void
     */
    private function migrateAll(): void
    {
        $tables = $this->schemaManager->listTables();

        foreach ($tables as $table) {
            if (in_array($table->getName(), array_merge(explode(',', $this->option('ignore')),  ['migrations'])))
                continue;

            if (in_array($table->getName(), $this->created))
                continue;

            $this->migrateForeignKeys($table->getForeignKeys());

            $this->migrateTable($table->getName());
        }
    }

    /**
     * @param ForeignKeyConstraint[] $foreignKeys
     * @return void
     */
    private function migrateForeignKeys(array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreign) {

            $tableDetails = $this->schemaManager->listTableDetails($foreign->getForeignTableName());

            $this->migrateForeignKeys($tableDetails->getForeignKeys());

            if (in_array($foreign->getForeignTableName(), $this->created))
                continue;

            $this->migrateTable($foreign->getForeignTableName());
        }
    }

    /**
     * @param string|null $table
     * @return void
     */
    private function migrateTable(?string $table): void
    {
        $schema = '';

        if($this->connection->hasTable($table)) {

            $tableDetails = $this->schemaManager->listTableDetails($table);

            $this->call("iseed",  ['tables' => $table, '--force' => true, '--clean' => true]);
            $this->created[] = $table;

            if(count($pivotTables = $this->getPivotTables($table)) == 2) {
                $this->call("ha-generator:migration:pivot",  ['tableOne' => $pivotTables['tableOne'], 'tableTwo' => $pivotTables['tableTwo']]);
                return;
            }

            foreach ($tableDetails->getColumns() as $column) {

                if (in_array($column->getName(), ['id', 'created_at', 'updated_at']))
                    continue;

                $type = $this->mapper[strtolower($column->getType()->getName())];

                $schema .= "{$column->getName()}";

                $schema .= $this->isForeign($tableDetails->getForeignKeys(), $column->getName()) ? ':bigInteger:foreign' : ":{$type}";

                $schema .= $column->getUnsigned() ? ':unsigned' : '';

                $schema .= (in_array($type, ['char', 'string']) AND $column->getLength()) ? "({$column->getLength()})" : '';

                $schema .= 'decimal' == $type ? "({$column->getPrecision()}, {$column->getScale()})" : '';

                $schema .= $this->getUnique($tableDetails->getIndexes(), $column->getName());

                $schema .= $column->getNotnull() ? '' : ':nullable';

                $schema .= $column->getDefault() ? ":default('{$column->getDefault()}')" : '';

                $schema .= ', ';
            }

            $schema = rtrim($schema, ', ');

            $noLogDetailed = !($this->option('log-detailed') AND !$this->option('no-log'));

            $params = ['name' => Str::singular($table), '--no-log' => $noLogDetailed, '--yes' => $this->option('yes'), '--schema' => $schema, '--api' => $this->option('api')];

            $this->call("ha-generator:crud",  $params);
        }
    }

    /**
     * @param ForeignKeyConstraint[] $keys
     * @param string $column
     * @return bool
     */
    private function isForeign(array $keys, string $column)
    {
        $fkColumns = [];
        foreach ($keys as $key) {
            $fkColumns = array_merge($fkColumns, $key->getColumns());
        }

        return in_array($column, $fkColumns);
    }

    /**
     * @param array $indexes
     * @param string $column
     * @return string
     */
    private function getUnique(array $indexes, string $column): string
    {
        foreach ($indexes as $index) {
            if (in_array($column, $index->getColumns()))
                return $index->isUnique() ? ':unique' : '';
        }

        return '';
    }


    /**
     * @param string $table
     * @return array
     */
    private function getPivotTables(string $table): array
    {
        $tables = [];
        $names = explode('_', $table);

        if(count($names) != 2)
            return [];

        if (in_array(Str::plural($names[0]), $this->created))
            $tables['tableOne'] = Str::plural($names[0]);

        if (in_array(Str::plural($names[1]), $this->created))
            $tables['tableTwo'] = Str::plural($names[1]);

        return $tables;
    }
}
