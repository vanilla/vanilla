<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Gdn_Module;
use Vanilla\Contracts\Addons\WidgetInterface;

/**
 * Class AbstractWidgetModule
 *
 * @package Vanilla\Widgets
 */
abstract class AbstractWidgetModule extends Gdn_Module implements WidgetInterface {

    /**
     * @var string $moduleName
     */
    public $moduleName = '';

    /**
     * Construct
     */
    public function __construct() {
        parent::__construct('', false);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetClass():string {
        return static::class;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID():string {
        return slugify(static::getWidgetName());
    }
}
