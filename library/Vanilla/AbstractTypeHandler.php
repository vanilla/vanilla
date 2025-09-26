<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use CategoryModel;

/**
 * Class AbstractTypeHandler
 *
 * @package Vanilla
 */
abstract class AbstractTypeHandler
{
    private $typeHandlerName = "";

    /**
     * Convert the handlers type.
     *
     * @param PostTypeConversionPayload $payload
     *
     * @return void
     */
    abstract public function convertTo(PostTypeConversionPayload $payload): void;

    /**
     * Convert any related records|data (ie. comments)
     *
     * @param PostTypeConversionPayload $payload
     *
     * @return void
     */
    abstract public function cleanUpRelatedData(PostTypeConversionPayload $payload): void;

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

    /**
     * @param PostTypeConversionPayload $payload
     *
     * @return bool
     */
    public function canConvert(PostTypeConversionPayload $payload): bool
    {
        $categoryModel = \Gdn::getContainer()->get(CategoryModel::class);

        return $categoryModel->isPostTypeAllowed($payload->targetCategoryRow, $payload->toPostTypeID);
    }
}
