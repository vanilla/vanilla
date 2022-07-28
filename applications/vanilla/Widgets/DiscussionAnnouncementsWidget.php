<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Modules\AnnouncementWidgetModule;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class DiscussionAnnouncementsWidget
 */
class DiscussionAnnouncementsWidget extends AnnouncementWidgetModule implements ReactWidgetInterface
{
    use DiscussionsWidgetSchemaTrait;

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "discussion.announcements";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Announcements";
    }

    /**
     * @inheritDoc
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
        return "/applications/dashboard/design/images/widgetIcons/announcements.svg";
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
