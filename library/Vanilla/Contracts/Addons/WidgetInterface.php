<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Addons;

use Garden\Schema\Schema;

/**
 * Interface WidgetInterface
 *
 * @package Vanilla\Contracts\Addons
 */
interface WidgetInterface {

    /**
     * Get a widgets schema.
     *
     * @return Schema
     */
    public static function getWidgetSchema(): Schema;

    /**
     * Get a widgets Name.
     *
     * @return string
     */
    public static function getWidgetName(): string;

    /**
     * Get a widgetsID
     *
     * @return string
     */
    public static function getWidgetID(): string;

    /**
     * Get a widgetsClass
     *
     * @return string
     */
    public static function getWidgetClass(): string;
}
