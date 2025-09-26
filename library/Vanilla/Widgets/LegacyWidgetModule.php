<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Gdn_Module;
use Vanilla\Contracts\Addons\WidgetInterface;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class AbstractWidgetModule
 *
 * @package Vanilla\Widgets
 *
 * @deprecated Use {@link ReactWidgetInterface} instead.
 */
abstract class LegacyWidgetModule extends Gdn_Module implements WidgetInterface
{
    /**
     * @var string $moduleName
     */
    public $moduleName = "";

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct("", false);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetClass(): string
    {
        return static::class;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return slugify(static::getWidgetName());
    }

    /**
     * Default is required. You should probably manually place your module.
     *
     * @return string
     */
    public function assetTarget()
    {
        return "Content";
    }
}
