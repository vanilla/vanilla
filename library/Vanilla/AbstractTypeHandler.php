<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Class AbstractTypeHandler
 *
 * @package Vanilla
 */
abstract class AbstractTypeHandler
{
    private $typeHandlerName = "";

    /**
     * Handler the type conversion
     *
     * @param array $from
     * @param string $to
     */
    abstract public function handleTypeConversion(array $from, string $to);

    /**
     * Convert the handlers type.
     *
     * @param array $record
     * @param string $to
     */
    abstract public function convertTo(array $record, string $to);

    /**
     * Convert any related records|data (ie. comments)
     *
     * @param array $record
     * @param string $to
     * @return bool
     */
    abstract public function cleanUpRelatedData(array $record, string $to);

    /**
     * Return the type handler name.
     *
     * @return string
     */
    public function getTypeHandlerName()
    {
        return $this->typeHandlerName;
    }

    /**
     * Set the type handler name.
     *
     * @param string $name
     */
    public function setTypeHandlerName($name = "")
    {
        $this->typeHandlerName = $name;
    }
}
