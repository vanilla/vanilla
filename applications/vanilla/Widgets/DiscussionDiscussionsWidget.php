<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Widgets\React\SectionAwareInterface;

/**
 * Class DiscussionDiscussionsWidget
 */
class DiscussionDiscussionsWidget extends DiscussionWidgetModule implements ReactWidgetInterface, SectionAwareInterface {

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string {
        return "discussion.discussions";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string {
        return "Discussion - Discussions";
    }

    /**
     * @inheridoc
     */
    public static function getComponentName(): string {
        // Temporarily this until we make a version supported grids and carousels.
        return "DiscussionListModule";
    }


    /**
     * @return array
     */
    public static function getRecommendedSectionIDs(): array {
        return [
            SectionOneColumn::getWidgetID(),
            SectionTwoColumns::getWidgetID(),
        ];
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema {
        $schema = SchemaUtils::composeSchemas(
            parent::getWidgetSchema(),
            self::containerOptionsSchema('containerOptions')
        );
        return $schema;
    }
}
