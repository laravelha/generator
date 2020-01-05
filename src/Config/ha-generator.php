<?php

return [

    'stringTypes' => [
        'char', 'string', 'text', 'mediumText', 'longText', 'json', 'jsonb'
    ],

    'integerTypes' => [
        'increments', 'integerIncrements', 'tinyIncrements', 'smallIncrements', 'mediumIncrements', 'bigIncrements',
        'integer', 'tinyInteger', 'smallInteger', 'mediumInteger', 'bigInteger',
        'unsignedInteger', 'unsignedTinyInteger', 'unsignedSmallInteger', 'unsignedMediumInteger', 'unsignedBigInteger'
    ],

    'floatTypes' => [
        'float', 'double', 'decimal', 'unsignedDecimal'
    ],

    'dateTypes' => [
        'date', 'dateTime', 'dateTimeTz',
        'time', 'timeTz', 'timestamp', 'timestampTz', 'timestamps',
        'timestamps', 'timestampsTz', 'softDeletes', 'softDeletesTz',
        'year',
    ],

    'bluePrintTypes' => [
        'increments', 'integerIncrements', 'tinyIncrements', 'smallIncrements', 'mediumIncrements', 'bigIncrements',
        'char', 'string', 'text', 'mediumText', 'longText',
        'integer', 'tinyInteger', 'smallInteger', 'mediumInteger', 'bigInteger',
        'unsignedInteger', 'unsignedTinyInteger', 'unsignedSmallInteger', 'unsignedMediumInteger', 'unsignedBigInteger',
        'float', 'double', 'decimal', 'unsignedDecimal',
        'boolean',
        'enum', 'set',
        'json', 'jsonb',
        'date', 'dateTime', 'dateTimeTz',
        'time', 'timeTz', 'timestamp', 'timestampTz', 'timestamps',
        'timestamps', 'timestampsTz', 'softDeletes', 'softDeletesTz',
        'year',
        'binary',
        'uuid',
        'ipAddress',
        'macAddress',
        'geometry', 'point', 'lineString', 'polygon', 'geometryCollection', 'multiPoint', 'multiLineString', 'multiPolygon', 'multiPolygonZ',
        'computed',
        'morphs', 'nullableMorphs', 'uuidMorphs', 'nullableUuidMorphs',
        'rememberToken',
        'foreign',
    ],

    'logFile' => 'ha-generator',

    'packagesFolder' => 'packages',

    'packagesNamespace' => 'Laravelha',

    'packagesVendor' => 'laravelha/',

    'packageConfigsFolder' => 'config',

    'packageMigrationsFolder' => 'database/migrations',
    'packageFactoriesFolder' => 'database/factories',

    'packageLangsFolder' => 'resource/lang',
    'packageViewsFolder' => 'resource/view',

    'packageRoutesFolder' => 'routes',
];
