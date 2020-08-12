<?php

namespace Laravelha\Generator\SyntaxBuilders;

class AbstractSintaxBuilder
{
    /**
     *  Root dir of stubs
     */
    const STUB_DIR = __DIR__ . '/../stubs/';

    /**
     * A template to be inserted.
     *
     * @var string
     */
    protected $template;

    /**
     * String types supported by bluePrint
     *
     * @var array
     */
    protected $stringTypes;

    /**
     * Integer types supported by bluePrint
     *
     * @var array
     */
    protected $integerTypes;

    /**
     * Float types supported by bluePrint
     *
     * @var array
     */
    protected $floatTypes;

    /**
     * Date types supported by bluePrint
     *
     * @var array
     */
    protected $dateTypes;

    /**
     * All types supported by bluePrint
     *
     * @var array
     */
    protected $bluePrintTypes;


    /**
     * AbstractSintaxBuilder constructor.
     */
    public function __construct()
    {
        $this->stringTypes = config('ha-generator.stringTypes');
        $this->integerTypes = config('ha-generator.integerTypes');
        $this->floatTypes = config('ha-generator.floatTypes');
        $this->dateTypes = config('ha-generator.dateTypes');
        $this->bluePrintTypes = config('ha-generator.bluePrintTypes');
    }

    /**
     * Determine if field has foreign constraint.
     *
     * @param  array $field
     * @return bool
     */
    protected function hasForeignConstraint(array $field): bool
    {
        return 'foreign' === $field['type'];
    }

    /**
     * Remove empty fields
     * @param array $fields
     * @return array
     */
    protected function removeEmpty(array $fields): array
    {
        foreach ($fields as $key => $field) {
            if($field == '')
                unset($fields[$key]);

        }

        return $fields;
    }

    /**
     * @return mixed
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = base_path('stubs/ha-generator/' . trim($stub, '/'))) && config('ha-generator.customStubs')
            ? $customPath
            : static::STUB_DIR.$stub;
    }
}
