<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class DiscussionDiscussionsWidget
 */
class DiscussionDiscussionsWidget extends DiscussionWidgetModule implements ReactWidgetInterface
{
    use DiscussionsWidgetSchemaTrait;

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string
    {
        return "discussion.discussions";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string
    {
        return "Discussions";
    }

    /**
     * @inheridoc
     */
    public static function getComponentName(): string
    {
        return "DiscussionsWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/discussions.svg";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            parent::getWidgetSchema(),
            self::optionsSchema(),
            self::containerOptionsSchema("containerOptions")
        );

        return $schema;
    }
}
