<?php
/**
 *
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Schemas;

/**
 * Class AbstractTabSearchFormSchema
 *
 * @package Vanilla\Community\Schemas
 */
abstract class AbstractTabSearchFormSchema
{
    /**
     * Get the schema.
     *
     * @return array
     */
    abstract public function schema(): array;

    /**
     * Get the tab ID.
     *
     * @return string
     */
    abstract public function getTabID(): string;

    /**
     * Get submit button text value.
     *
     * @return string
     */
    abstract public function getSubmitButtonText(): string;

    /**
     * Define the tab as default entry.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return true;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    abstract public function getTitle(): string;
}
